<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $productos = Producto::with(['categoria', 'unidad'])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'codigo_barras' => $p->codigo_barras,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre ?? 'Sin categoría',
                'categoria_id' => $p->categoria_id,
                'unidad' => $p->unidad->abreviatura ?? 'UND',
                'precio' => (float) $p->precio_venta,
                'stock' => (float) $p->stock,
            ]);

        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'numero_documento']);

        $categorias = $productos->pluck('categoria', 'categoria_id')->unique()->sortBy(fn ($v) => $v);

        return view('pos.index', compact('productos', 'clientes', 'categorias'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['nullable', \Illuminate\Validation\Rule::exists('clientes', 'id')->where('empresa_id', auth()->user()->empresa_id)],
            'tipo_comprobante' => ['required', 'in:ticket,boleta,factura'],
            'metodo_pago' => ['required', 'in:efectivo,tarjeta,transferencia,yape,plin,credito'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'items' => ['required', 'json'],
        ]);

        $items = collect(json_decode($datos['items'], true));

        if ($items->isEmpty()) {
            return back()->with('error', 'El carrito está vacío.');
        }

        // Una venta a crédito debe ir asociada a un cliente identificable.
        if ($datos['metodo_pago'] === 'credito' && empty($datos['cliente_id'])) {
            return back()->with('error', 'Para una venta a crédito debes seleccionar un cliente registrado.');
        }

        $impuestoPct = (float) (auth()->user()->empresa->impuesto ?? 18);

        try {
            $venta = DB::transaction(function () use ($datos, $items, $impuestoPct) {
                $empresaId = auth()->user()->empresa_id;

                // Validar stock con bloqueo para evitar ventas simultáneas
                $productos = Producto::whereIn('id', $items->pluck('id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $p = $productos->get($item['id']);
                    if (! $p) {
                        throw new \RuntimeException("Producto no encontrado (ID {$item['id']}).");
                    }
                    if ((float) $item['cantidad'] <= 0) {
                        throw new \RuntimeException("Cantidad inválida para «{$p->nombre}».");
                    }
                    if ($p->stock < $item['cantidad']) {
                        throw new \RuntimeException("Stock insuficiente para «{$p->nombre}» (disponible: " . ($p->stock + 0) . ').');
                    }
                }

                // Correlativo por empresa
                $ultimo = Venta::withoutGlobalScope('empresa')
                    ->where('empresa_id', $empresaId)
                    ->lockForUpdate()
                    ->count();
                $numero = 'V-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);

                $total = round($items->sum(fn ($i) => $i['cantidad'] * $i['precio']), 2);
                $descuento = min(round((float) ($datos['descuento'] ?? 0), 2), $total);
                $totalFinal = $total - $descuento;
                $subtotal = round($totalFinal / (1 + $impuestoPct / 100), 2);

                $esCredito = $datos['metodo_pago'] === 'credito';
                $vencimiento = $esCredito
                    ? ($datos['fecha_vencimiento'] ?? now()->addDays(30)->toDateString())
                    : null;

                $venta = Venta::create([
                    'cliente_id' => $datos['cliente_id'] ?: null,
                    'user_id' => auth()->id(),
                    'numero' => $numero,
                    'tipo_comprobante' => $datos['tipo_comprobante'],
                    'fecha' => now(),
                    'subtotal' => $subtotal,
                    'impuesto' => round($totalFinal - $subtotal, 2),
                    'descuento' => $descuento,
                    'total' => $totalFinal,
                    'saldo' => $esCredito ? $totalFinal : 0,
                    'fecha_vencimiento' => $vencimiento,
                    'metodo_pago' => $datos['metodo_pago'],
                    'estado' => 'completada',
                ]);

                foreach ($items as $item) {
                    $p = $productos->get($item['id']);

                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $p->id,
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'subtotal' => round($item['cantidad'] * $item['precio'], 2),
                    ]);

                    $p->decrement('stock', $item['cantidad']);

                    MovimientoInventario::create([
                        'producto_id' => $p->id,
                        'user_id' => auth()->id(),
                        'tipo' => 'salida',
                        'cantidad' => $item['cantidad'],
                        'motivo' => "Venta {$numero}",
                        'fecha' => now(),
                    ]);
                }

                return $venta;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $moneda = auth()->user()->empresa->moneda ?? 'S/';
        $mensaje = "Venta {$venta->numero} registrada por {$moneda} " . number_format($venta->total, 2) . '.';
        if ($venta->esCredito()) {
            $mensaje .= ' Quedó como cuenta por cobrar (vence el ' . $venta->fecha_vencimiento->format('d/m/Y') . ').';
        }

        // Emitir el comprobante electronico (boleta/factura) sin bloquear el POS.
        if (config('facturacion.enabled') && auth()->user()->empresa->factura_activa
            && in_array($venta->tipo_comprobante, ['boleta', 'factura'], true)) {
            \App\Jobs\EmitirComprobanteJob::dispatchAfterResponse($venta->id);
            $mensaje .= ' Comprobante electrónico en emisión.';
        }

        return redirect()->route('pos.index')
            ->with('ok', $mensaje)
            ->with('venta_id', $venta->id);
    }

    public function recibo(Venta $venta)
    {
        $venta->load(['detalles.producto.unidad', 'cliente', 'user']);

        return view('pos.recibo', ['venta' => $venta, 'empresa' => auth()->user()->empresa]);
    }
}
