<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Pago;
use App\Models\Proveedor;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaController extends Controller
{
    /* =========================================================
     |  CUENTAS POR COBRAR  (ventas a crédito con saldo > 0)
     * ========================================================= */
    public function porCobrar(Request $request)
    {
        $hoy = Carbon::today();

        $query = Venta::with('cliente')
            ->where('metodo_pago', 'credito')
            ->where('estado', '!=', 'anulada')
            ->where('saldo', '>', 0)
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$b}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%")
                        ->orWhere('numero_documento', 'like', "%{$b}%")));
            })
            ->when($request->estado === 'vencidas', fn ($q) => $q->whereDate('fecha_vencimiento', '<', $hoy))
            ->when($request->estado === 'porvencer', fn ($q) => $q->whereDate('fecha_vencimiento', '>=', $hoy))
            ->when($request->estado === 'parciales', fn ($q) => $q->whereColumn('saldo', '<', 'total'));

        // Indicadores globales (sin paginar)
        $base = Venta::where('metodo_pago', 'credito')->where('estado', '!=', 'anulada')->where('saldo', '>', 0);
        $totalPorCobrar = (clone $base)->sum('saldo');
        $totalVencido = (clone $base)->whereDate('fecha_vencimiento', '<', $hoy)->sum('saldo');
        $numDocs = (clone $base)->count();
        $numVencidas = (clone $base)->whereDate('fecha_vencimiento', '<', $hoy)->count();

        $ventas = $query->orderByRaw('fecha_vencimiento is null, fecha_vencimiento asc')
            ->paginate(15)->withQueryString();

        $clientes = Cliente::whereIn('id', Venta::where('metodo_pago', 'credito')->where('saldo', '>', 0)->pluck('cliente_id')->filter()->unique())
            ->orderBy('nombre')->get(['id', 'nombre']);

        return view('cuentas.por_cobrar', compact(
            'ventas', 'totalPorCobrar', 'totalVencido', 'numDocs', 'numVencidas', 'clientes'
        ));
    }

    public function cobrarDetalle(Venta $venta)
    {
        if (! $venta->esCredito()) {
            return redirect()->route('cuentas.cobrar')->with('error', 'Esta venta no es a crédito.');
        }

        $venta->load(['cliente', 'user', 'detalles.producto', 'pagos.user']);

        return view('cuentas.cobrar_detalle', compact('venta'));
    }

    public function registrarCobro(Request $request, Venta $venta)
    {
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['required', 'in:efectivo,tarjeta,transferencia,yape,plin,cheque,deposito'],
            'fecha' => ['required', 'date'],
            'referencia' => ['nullable', 'string', 'max:60'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        if ($venta->estado === 'anulada' || ! $venta->esCredito()) {
            return back()->with('error', 'No se pueden registrar cobros en esta venta.');
        }

        $monto = round((float) $datos['monto'], 2);
        if ($monto > (float) $venta->saldo + 0.001) {
            return back()->with('error', 'El monto (' . number_format($monto, 2) . ') supera el saldo pendiente (' . number_format($venta->saldo, 2) . ').');
        }

        DB::transaction(function () use ($venta, $datos, $monto) {
            $v = Venta::where('id', $venta->id)->lockForUpdate()->first();

            Pago::create([
                'tipo' => 'cobro',
                'venta_id' => $v->id,
                'user_id' => auth()->id(),
                'monto' => $monto,
                'metodo_pago' => $datos['metodo_pago'],
                'fecha' => $datos['fecha'],
                'referencia' => $datos['referencia'] ?? null,
                'nota' => $datos['nota'] ?? null,
            ]);

            $v->decrement('saldo', $monto);
        });

        $venta->refresh();
        $msg = "Cobro de " . (auth()->user()->empresa->moneda ?? 'S/') . " {$monto} registrado.";
        $msg .= (float) $venta->saldo <= 0 ? ' La venta quedó totalmente pagada.' : ' Saldo restante: ' . number_format($venta->saldo, 2) . '.';

        return back()->with('ok', $msg);
    }

    /* =========================================================
     |  CUENTAS POR PAGAR  (compras a crédito con saldo > 0)
     * ========================================================= */
    public function porPagar(Request $request)
    {
        $hoy = Carbon::today();

        $query = Compra::with('proveedor')
            ->where('a_credito', true)
            ->where('estado', '!=', 'anulada')
            ->where('saldo', '>', 0)
            ->when($request->filled('proveedor_id'), fn ($q) => $q->where('proveedor_id', $request->proveedor_id))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$b}%")
                    ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$b}%")
                        ->orWhere('ruc', 'like', "%{$b}%")));
            })
            ->when($request->estado === 'vencidas', fn ($q) => $q->whereDate('fecha_vencimiento', '<', $hoy))
            ->when($request->estado === 'porvencer', fn ($q) => $q->whereDate('fecha_vencimiento', '>=', $hoy))
            ->when($request->estado === 'parciales', fn ($q) => $q->whereColumn('saldo', '<', 'total'));

        $base = Compra::where('a_credito', true)->where('estado', '!=', 'anulada')->where('saldo', '>', 0);
        $totalPorPagar = (clone $base)->sum('saldo');
        $totalVencido = (clone $base)->whereDate('fecha_vencimiento', '<', $hoy)->sum('saldo');
        $numDocs = (clone $base)->count();
        $numVencidas = (clone $base)->whereDate('fecha_vencimiento', '<', $hoy)->count();

        $compras = $query->orderByRaw('fecha_vencimiento is null, fecha_vencimiento asc')
            ->paginate(15)->withQueryString();

        $proveedores = Proveedor::whereIn('id', Compra::where('a_credito', true)->where('saldo', '>', 0)->pluck('proveedor_id')->filter()->unique())
            ->orderBy('razon_social')->get(['id', 'razon_social']);

        return view('cuentas.por_pagar', compact(
            'compras', 'totalPorPagar', 'totalVencido', 'numDocs', 'numVencidas', 'proveedores'
        ));
    }

    public function pagarDetalle(Compra $compra)
    {
        if (! $compra->a_credito) {
            return redirect()->route('cuentas.pagar')->with('error', 'Esta compra no es a crédito.');
        }

        $compra->load(['proveedor', 'user', 'detalles.producto', 'pagos.user']);

        return view('cuentas.pagar_detalle', compact('compra'));
    }

    public function registrarPago(Request $request, Compra $compra)
    {
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['required', 'in:efectivo,tarjeta,transferencia,yape,plin,cheque,deposito'],
            'fecha' => ['required', 'date'],
            'referencia' => ['nullable', 'string', 'max:60'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        if ($compra->estado === 'anulada' || ! $compra->a_credito) {
            return back()->with('error', 'No se pueden registrar pagos en esta compra.');
        }

        $monto = round((float) $datos['monto'], 2);
        if ($monto > (float) $compra->saldo + 0.001) {
            return back()->with('error', 'El monto (' . number_format($monto, 2) . ') supera el saldo pendiente (' . number_format($compra->saldo, 2) . ').');
        }

        DB::transaction(function () use ($compra, $datos, $monto) {
            $c = Compra::where('id', $compra->id)->lockForUpdate()->first();

            Pago::create([
                'tipo' => 'pago',
                'compra_id' => $c->id,
                'user_id' => auth()->id(),
                'monto' => $monto,
                'metodo_pago' => $datos['metodo_pago'],
                'fecha' => $datos['fecha'],
                'referencia' => $datos['referencia'] ?? null,
                'nota' => $datos['nota'] ?? null,
            ]);

            $c->decrement('saldo', $monto);
        });

        $compra->refresh();
        $msg = "Pago de " . (auth()->user()->empresa->moneda ?? 'S/') . " {$monto} registrado.";
        $msg .= (float) $compra->saldo <= 0 ? ' La compra quedó totalmente pagada.' : ' Saldo restante: ' . number_format($compra->saldo, 2) . '.';

        return back()->with('ok', $msg);
    }

    /* =========================================================
     |  ESTADO DE CUENTA DEL CLIENTE
     * ========================================================= */
    public function estadoCliente(Cliente $cliente)
    {
        $ventas = Venta::where('cliente_id', $cliente->id)
            ->where('metodo_pago', 'credito')
            ->where('estado', '!=', 'anulada')
            ->with('pagos')
            ->orderByDesc('fecha')
            ->get();

        $totalCredito = $ventas->sum('total');
        $totalPagado = $ventas->sum(fn ($v) => $v->pagado());
        $totalSaldo = $ventas->sum('saldo');
        $totalVencido = $ventas->filter->vencida()->sum('saldo');

        return view('cuentas.estado_cliente', compact(
            'cliente', 'ventas', 'totalCredito', 'totalPagado', 'totalSaldo', 'totalVencido'
        ));
    }
}
