<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CotizacionController extends Controller
{
    public function index(Request $request)
    {
        // Marcar vencidas automáticamente
        Cotizacion::where('estado', 'vigente')
            ->whereNotNull('vencimiento')
            ->whereDate('vencimiento', '<', today())
            ->update(['estado' => 'vencida']);

        $cotizaciones = Cotizacion::with(['cliente', 'user'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$b}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%")));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create()
    {
        $productos = Producto::with('unidad')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'unidad' => $p->unidad->abreviatura ?? 'UND',
                'precio' => (float) $p->precio_venta,
                'stock' => (float) $p->stock,
            ]);

        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'numero_documento']);

        return view('cotizaciones.form', compact('productos', 'clientes'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['nullable', Rule::exists('clientes', 'id')->where('empresa_id', auth()->user()->empresa_id)],
            'vencimiento' => ['nullable', 'date', 'after_or_equal:today'],
            'items' => ['required', 'json'],
        ], ['vencimiento.after_or_equal' => 'El vencimiento no puede ser una fecha pasada.']);

        $items = collect(json_decode($datos['items'], true))
            ->filter(fn ($i) => (float) ($i['cantidad'] ?? 0) > 0);

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Agrega al menos un producto.');
        }

        $cotizacion = DB::transaction(function () use ($datos, $items) {
            $empresaId = auth()->user()->empresa_id;

            $ultimo = Cotizacion::withoutGlobalScope('empresa')
                ->where('empresa_id', $empresaId)
                ->lockForUpdate()
                ->count();

            $cotizacion = Cotizacion::create([
                'cliente_id' => $datos['cliente_id'] ?: null,
                'user_id' => auth()->id(),
                'numero' => 'COT-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT),
                'fecha' => today(),
                'vencimiento' => $datos['vencimiento'] ?: today()->addDays(7),
                'total' => round($items->sum(fn ($i) => $i['cantidad'] * $i['precio']), 2),
                'estado' => 'vigente',
            ]);

            foreach ($items as $item) {
                CotizacionDetalle::create([
                    'cotizacion_id' => $cotizacion->id,
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'subtotal' => round($item['cantidad'] * $item['precio'], 2),
                ]);
            }

            return $cotizacion;
        });

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('ok', "Cotización {$cotizacion->numero} creada.");
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load(['detalles.producto.unidad', 'cliente', 'user']);

        return view('cotizaciones.show', ['cotizacion' => $cotizacion, 'empresa' => auth()->user()->empresa]);
    }

    public function convertir(Cotizacion $cotizacion)
    {
        if (! in_array($cotizacion->estado, ['vigente', 'vencida'])) {
            return back()->with('error', 'Esta cotización ya fue aceptada o anulada.');
        }

        $impuestoPct = (float) (auth()->user()->empresa->impuesto ?? 18);

        try {
            $venta = DB::transaction(function () use ($cotizacion, $impuestoPct) {
                $cotizacion->load('detalles');
                $empresaId = auth()->user()->empresa_id;

                $productos = Producto::whereIn('id', $cotizacion->detalles->pluck('producto_id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($cotizacion->detalles as $d) {
                    $p = $productos->get($d->producto_id);
                    if (! $p || $p->stock < $d->cantidad) {
                        throw new \RuntimeException('Stock insuficiente para «' . ($p->nombre ?? 'producto') . '» (disponible: ' . (($p->stock ?? 0) + 0) . ').');
                    }
                }

                $ultimo = Venta::withoutGlobalScope('empresa')
                    ->where('empresa_id', $empresaId)
                    ->lockForUpdate()
                    ->count();
                $numero = 'V-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);

                $total = (float) $cotizacion->total;
                $subtotal = round($total / (1 + $impuestoPct / 100), 2);

                $venta = Venta::create([
                    'cliente_id' => $cotizacion->cliente_id,
                    'user_id' => auth()->id(),
                    'numero' => $numero,
                    'tipo_comprobante' => 'ticket',
                    'fecha' => now(),
                    'subtotal' => $subtotal,
                    'impuesto' => round($total - $subtotal, 2),
                    'descuento' => 0,
                    'total' => $total,
                    'metodo_pago' => 'efectivo',
                    'estado' => 'completada',
                ]);

                foreach ($cotizacion->detalles as $d) {
                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $d->producto_id,
                        'cantidad' => $d->cantidad,
                        'precio' => $d->precio,
                        'subtotal' => $d->subtotal,
                    ]);

                    $productos->get($d->producto_id)->decrement('stock', $d->cantidad);

                    MovimientoInventario::create([
                        'producto_id' => $d->producto_id,
                        'user_id' => auth()->id(),
                        'tipo' => 'salida',
                        'cantidad' => $d->cantidad,
                        'motivo' => "Venta {$numero} (cotización {$cotizacion->numero})",
                        'fecha' => now(),
                    ]);
                }

                $cotizacion->update(['estado' => 'aceptada']);

                return $venta;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)
            ->with('ok', "Cotización convertida en la venta {$venta->numero}.");
    }

    public function anular(Cotizacion $cotizacion)
    {
        if ($cotizacion->estado === 'aceptada') {
            return back()->with('error', 'No se puede anular una cotización ya convertida en venta.');
        }

        $cotizacion->update(['estado' => 'anulada']);

        return back()->with('ok', "Cotización {$cotizacion->numero} anulada.");
    }
}
