<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\SuscripcionPago;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();

        $recaudadoMes = SuscripcionPago::where('fecha_pago', '>=', $inicioMes)->sum('monto');
        $pagosMes = SuscripcionPago::where('fecha_pago', '>=', $inicioMes)->count();

        // MRR (planes activos)
        $mrr = 0.0;
        foreach (Empresa::where('activo', true)->pluck('plan') as $plan) {
            $mrr += Empresa::PRECIOS_PLAN[$plan] ?? 0;
        }

        // Morosos: plan vencido
        $morosos = Empresa::whereNotNull('plan_vence')
            ->whereDate('plan_vence', '<', $hoy)
            ->orderBy('plan_vence')
            ->get();

        // Por vencer en los próximos 15 días
        $porVencer = Empresa::where('activo', true)
            ->whereNotNull('plan_vence')
            ->whereBetween('plan_vence', [$hoy, $hoy->copy()->addDays(15)])
            ->orderBy('plan_vence')
            ->get();

        $pagos = SuscripcionPago::with(['empresa', 'registrador'])
            ->orderByDesc('fecha_pago')->orderByDesc('id')
            ->paginate(15);

        return view('superadmin.facturacion.index', compact(
            'recaudadoMes', 'pagosMes', 'mrr', 'morosos', 'porVencer', 'pagos'
        ));
    }

    public function store(Request $request, Empresa $empresa)
    {
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'meses' => ['required', 'integer', 'min:1', 'max:36'],
            'fecha_pago' => ['required', 'date'],
            'metodo' => ['required', 'in:transferencia,tarjeta,efectivo,deposito,yape,plin,cheque'],
            'referencia' => ['nullable', 'string', 'max:60'],
            'nota' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($empresa, $datos) {
            // El nuevo periodo arranca desde el vencimiento vigente (si no expiró) o desde hoy
            $base = $empresa->plan_vence && $empresa->plan_vence->isFuture()
                ? $empresa->plan_vence->copy()
                : Carbon::today();

            $hasta = $base->copy()->addMonths((int) $datos['meses']);

            SuscripcionPago::create([
                'empresa_id' => $empresa->id,
                'plan' => $empresa->plan,
                'monto' => $datos['monto'],
                'meses' => $datos['meses'],
                'periodo_desde' => $base->toDateString(),
                'periodo_hasta' => $hasta->toDateString(),
                'fecha_pago' => $datos['fecha_pago'],
                'metodo' => $datos['metodo'],
                'referencia' => $datos['referencia'] ?? null,
                'nota' => $datos['nota'] ?? null,
                'registrado_por' => auth()->id(),
            ]);

            $empresa->plan_vence = $hasta->toDateString();

            // Si estaba suspendida por falta de pago, el pago la reactiva
            if (! $empresa->activo) {
                $empresa->activo = true;
                $empresa->suspendido_motivo = null;
                User::where('empresa_id', $empresa->id)->update(['activo' => true]);
            }

            $empresa->save();
        });

        return redirect()->route('superadmin.empresas.show', $empresa)
            ->with('ok', "Pago de suscripción registrado. El plan vence ahora el {$empresa->plan_vence->format('d/m/Y')}.");
    }
}
