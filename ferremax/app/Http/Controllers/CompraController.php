<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'user'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$b}%")
                    ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$b}%")
                        ->orWhere('ruc', 'like', "%{$b}%")));
            })
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado));

        $resumen = (clone $query)->where('estado', 'recibida')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->first();

        $compras = $query->orderByDesc('fecha')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('compras.index', compact('compras', 'resumen'));
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
                'precio_compra' => (float) $p->precio_compra,
                'stock' => (float) $p->stock,
            ]);

        $proveedores = Proveedor::where('activo', true)->orderBy('razon_social')->get(['id', 'razon_social', 'ruc']);

        return view('compras.form', compact('productos', 'proveedores'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', Rule::exists('proveedores', 'id')->where('empresa_id', auth()->user()->empresa_id)],
            'fecha' => ['required', 'date'],
            'actualizar_precios' => ['nullable', 'boolean'],
            'a_credito' => ['nullable', 'boolean'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'items' => ['required', 'json'],
        ], [
            'proveedor_id.required' => 'Selecciona un proveedor.',
            'fecha.required' => 'La fecha es obligatoria.',
        ]);

        $items = collect(json_decode($datos['items'], true));

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Agrega al menos un producto a la compra.');
        }

        $impuestoPct = (float) (auth()->user()->empresa->impuesto ?? 18);

        try {
            $compra = DB::transaction(function () use ($datos, $items, $impuestoPct, $request) {
                $empresaId = auth()->user()->empresa_id;

                $productos = Producto::whereIn('id', $items->pluck('id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $p = $productos->get($item['id']);
                    if (! $p) {
                        throw new \RuntimeException("Producto no encontrado (ID {$item['id']}).");
                    }
                    if ((float) $item['cantidad'] <= 0 || (float) $item['precio'] < 0) {
                        throw new \RuntimeException("Cantidad o costo inválido para «{$p->nombre}».");
                    }
                }

                $ultimo = Compra::withoutGlobalScope('empresa')
                    ->where('empresa_id', $empresaId)
                    ->lockForUpdate()
                    ->count();
                $numero = 'C-' . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);

                $total = round($items->sum(fn ($i) => $i['cantidad'] * $i['precio']), 2);
                $subtotal = round($total / (1 + $impuestoPct / 100), 2);

                $aCredito = $request->boolean('a_credito');
                $vencimiento = $aCredito
                    ? ($datos['fecha_vencimiento'] ?? \Carbon\Carbon::parse($datos['fecha'])->addDays(30)->toDateString())
                    : null;

                $compra = Compra::create([
                    'proveedor_id' => $datos['proveedor_id'],
                    'user_id' => auth()->id(),
                    'numero' => $numero,
                    'fecha' => $datos['fecha'],
                    'subtotal' => $subtotal,
                    'impuesto' => round($total - $subtotal, 2),
                    'total' => $total,
                    'a_credito' => $aCredito,
                    'saldo' => $aCredito ? $total : 0,
                    'fecha_vencimiento' => $vencimiento,
                    'estado' => 'recibida',
                ]);

                foreach ($items as $item) {
                    $p = $productos->get($item['id']);

                    CompraDetalle::create([
                        'compra_id' => $compra->id,
                        'producto_id' => $p->id,
                        'cantidad' => $item['cantidad'],
                        'precio' => $item['precio'],
                        'subtotal' => round($item['cantidad'] * $item['precio'], 2),
                    ]);

                    $p->increment('stock', $item['cantidad']);

                    if ($request->boolean('actualizar_precios') && (float) $item['precio'] != (float) $p->precio_compra) {
                        $p->update(['precio_compra' => $item['precio']]);
                    }

                    MovimientoInventario::create([
                        'producto_id' => $p->id,
                        'user_id' => auth()->id(),
                        'tipo' => 'entrada',
                        'cantidad' => $item['cantidad'],
                        'motivo' => "Compra {$numero}",
                        'fecha' => now(),
                    ]);
                }

                return $compra;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('compras.show', $compra)
            ->with('ok', "Compra {$compra->numero} registrada. El stock fue actualizado.");
    }

    public function show(Compra $compra)
    {
        $compra->load(['detalles.producto.unidad', 'proveedor', 'user']);

        return view('compras.show', compact('compra'));
    }

    public function anular(Compra $compra)
    {
        if ($compra->estado === 'anulada') {
            return back()->with('error', 'Esta compra ya está anulada.');
        }

        try {
            DB::transaction(function () use ($compra) {
                $compra->load('detalles');

                foreach ($compra->detalles as $d) {
                    $p = Producto::withoutGlobalScope('empresa')
                        ->where('id', $d->producto_id)
                        ->lockForUpdate()
                        ->first();

                    if ($p && $p->stock < $d->cantidad) {
                        throw new \RuntimeException("No se puede anular: el stock actual de «{$p->nombre}» (" . ($p->stock + 0) . ") es menor a la cantidad comprada (" . ($d->cantidad + 0) . "). Parte ya fue vendida.");
                    }
                }

                foreach ($compra->detalles as $d) {
                    Producto::withoutGlobalScope('empresa')
                        ->where('id', $d->producto_id)
                        ->decrement('stock', $d->cantidad);

                    MovimientoInventario::create([
                        'producto_id' => $d->producto_id,
                        'user_id' => auth()->id(),
                        'tipo' => 'salida',
                        'cantidad' => $d->cantidad,
                        'motivo' => "Anulación de compra {$compra->numero}",
                        'fecha' => now(),
                    ]);
                }

                $compra->update(['estado' => 'anulada', 'saldo' => 0]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', "Compra {$compra->numero} anulada. El stock fue revertido.");
    }
}
