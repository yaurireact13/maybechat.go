<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index()
    {
        $cajaAbierta = Caja::where('estado', 'abierta')->latest('fecha_apertura')->first();

        $ventasTurno = null;
        if ($cajaAbierta) {
            $ventasTurno = Venta::where('estado', 'completada')
                ->where('fecha', '>=', $cajaAbierta->fecha_apertura)
                ->selectRaw("COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total,
                    COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as efectivo,
                    COALESCE(SUM(CASE WHEN metodo_pago != 'efectivo' THEN total ELSE 0 END), 0) as otros")
                ->first();
        }

        $historial = Caja::with('user')->orderByDesc('fecha_apertura')->paginate(10);

        return view('caja.index', compact('cajaAbierta', 'ventasTurno', 'historial'));
    }

    public function abrir(Request $request)
    {
        if (Caja::where('estado', 'abierta')->exists()) {
            return back()->with('error', 'Ya hay una caja abierta. Ciérrala antes de abrir otra.');
        }

        $datos = $request->validate([
            'monto_apertura' => ['required', 'numeric', 'min:0'],
        ], ['monto_apertura.required' => 'Indica el monto inicial en efectivo.']);

        Caja::create([
            'user_id' => auth()->id(),
            'fecha_apertura' => now(),
            'monto_apertura' => $datos['monto_apertura'],
            'estado' => 'abierta',
        ]);

        return back()->with('ok', 'Caja abierta. ¡Buen turno!');
    }

    public function cerrar(Request $request, Caja $caja)
    {
        if ($caja->estado !== 'abierta') {
            return back()->with('error', 'Esta caja ya está cerrada.');
        }

        $datos = $request->validate([
            'monto_cierre' => ['required', 'numeric', 'min:0'],
        ], ['monto_cierre.required' => 'Indica el efectivo contado al cierre.']);

        $caja->update([
            'fecha_cierre' => now(),
            'monto_cierre' => $datos['monto_cierre'],
            'estado' => 'cerrada',
        ]);

        return back()->with('ok', 'Caja cerrada correctamente.');
    }
}
