<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $movimientos = MovimientoInventario::with(['producto', 'user'])
            ->when($request->filled('producto_id'), fn ($q) => $q->where('producto_id', $request->producto_id))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $productos = Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'stock', 'stock_minimo']);

        $resumen = [
            'valorizado' => Producto::where('activo', true)->sum(DB::raw('stock * precio_compra')),
            'unidades' => Producto::where('activo', true)->sum('stock'),
            'stock_bajo' => Producto::where('activo', true)->stockBajo()->count(),
            'sin_stock' => Producto::where('activo', true)->where('stock', '<=', 0)->count(),
        ];

        return view('inventario.index', compact('movimientos', 'productos', 'resumen'));
    }

    public function ajustar(Request $request)
    {
        $datos = $request->validate([
            'producto_id' => ['required', Rule::exists('productos', 'id')->where('empresa_id', auth()->user()->empresa_id)],
            'tipo' => ['required', 'in:entrada,salida'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'producto_id.required' => 'Selecciona un producto.',
            'cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'motivo.required' => 'Indica el motivo del ajuste.',
        ]);

        try {
            DB::transaction(function () use ($datos) {
                $p = Producto::where('id', $datos['producto_id'])->lockForUpdate()->firstOrFail();

                if ($datos['tipo'] === 'salida' && $p->stock < $datos['cantidad']) {
                    throw new \RuntimeException("Stock insuficiente: «{$p->nombre}» solo tiene " . ($p->stock + 0) . ' unidades.');
                }

                $datos['tipo'] === 'entrada'
                    ? $p->increment('stock', $datos['cantidad'])
                    : $p->decrement('stock', $datos['cantidad']);

                MovimientoInventario::create([
                    'producto_id' => $p->id,
                    'user_id' => auth()->id(),
                    'tipo' => $datos['tipo'],
                    'cantidad' => $datos['cantidad'],
                    'motivo' => 'Ajuste: ' . $datos['motivo'],
                    'fecha' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'Ajuste de inventario registrado.');
    }
}
