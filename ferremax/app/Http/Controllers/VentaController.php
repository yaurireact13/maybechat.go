<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'user', 'comprobanteElectronico'])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$b}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%")
                        ->orWhere('numero_documento', 'like', "%{$b}%")));
            })
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->when($request->filled('metodo_pago'), fn ($q) => $q->where('metodo_pago', $request->metodo_pago))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado));

        // Totales del filtro aplicado (solo completadas)
        $resumen = (clone $query)->where('estado', 'completada')
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->first();

        $ventas = $query->orderByDesc('fecha')->paginate(15)->withQueryString();

        return view('ventas.index', compact('ventas', 'resumen'));
    }

    public function show(Venta $venta)
    {
        $venta->load(['detalles.producto.unidad', 'cliente', 'user', 'comprobanteElectronico']);

        return view('ventas.show', compact('venta'));
    }

    public function anular(Request $request, Venta $venta)
    {
        if ($venta->estado === 'anulada') {
            return back()->with('error', 'Esta venta ya está anulada.');
        }

        DB::transaction(function () use ($venta) {
            $venta->load('detalles');

            foreach ($venta->detalles as $d) {
                Producto::withoutGlobalScope('empresa')
                    ->where('id', $d->producto_id)
                    ->increment('stock', $d->cantidad);

                MovimientoInventario::create([
                    'producto_id' => $d->producto_id,
                    'user_id' => auth()->id(),
                    'tipo' => 'entrada',
                    'cantidad' => $d->cantidad,
                    'motivo' => "Anulación de venta {$venta->numero}",
                    'fecha' => now(),
                ]);
            }

            $venta->update(['estado' => 'anulada', 'saldo' => 0]);
        });

        return back()->with('ok', "Venta {$venta->numero} anulada. El stock fue devuelto al inventario.");
    }
}
