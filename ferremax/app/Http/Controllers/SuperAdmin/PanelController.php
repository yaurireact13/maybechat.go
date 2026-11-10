<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function dashboard()
    {
        $inicioMes = Carbon::now()->startOfMonth();

        $totalEmpresas = Empresa::count();
        $empresasActivas = Empresa::where('activo', true)->count();
        $empresasSuspendidas = $totalEmpresas - $empresasActivas;
        $totalUsuarios = User::where('rol', '!=', 'superadmin')->count();

        // Empresas por plan + MRR (solo planes activos generan ingreso recurrente)
        $porPlan = Empresa::select('plan', DB::raw('COUNT(*) as total'), DB::raw('SUM(activo) as activas'))
            ->groupBy('plan')->get()->keyBy('plan');

        $mrr = 0.0;
        $planes = [];
        foreach (['basico', 'pro', 'premium'] as $plan) {
            $fila = $porPlan->get($plan);
            $activas = (int) ($fila->activas ?? 0);
            $precio = Empresa::PRECIOS_PLAN[$plan];
            $mrr += $activas * $precio;
            $planes[] = [
                'plan' => $plan,
                'precio' => $precio,
                'total' => (int) ($fila->total ?? 0),
                'activas' => $activas,
                'ingreso' => $activas * $precio,
            ];
        }

        // Ventas globales del mes (scope multi-tenant omitido para el super admin)
        $ventasGlobalMes = Venta::where('estado', 'completada')->where('fecha', '>=', $inicioMes)->sum('total');
        $numVentasMes = Venta::where('estado', 'completada')->where('fecha', '>=', $inicioMes)->count();

        // Nuevas empresas por mes (6 meses)
        $altas6meses = collect(range(5, 0))->map(function ($m) {
            $ini = Carbon::now()->subMonths($m)->startOfMonth();
            $fin = Carbon::now()->subMonths($m)->endOfMonth();

            return [
                'mes' => $ini->locale('es')->isoFormat('MMM YYYY'),
                'total' => Empresa::whereBetween('created_at', [$ini, $fin])->count(),
            ];
        });

        // Top empresas por facturación del mes
        $topEmpresas = Venta::select('empresa_id', DB::raw('SUM(total) as total'))
            ->where('estado', 'completada')->where('fecha', '>=', $inicioMes)
            ->groupBy('empresa_id')->orderByDesc('total')->limit(5)->get();
        $nombres = Empresa::whereIn('id', $topEmpresas->pluck('empresa_id'))->pluck('nombre', 'id');

        $ultimasEmpresas = Empresa::withCount('users')->latest()->limit(6)->get();
        $planesVencidos = Empresa::whereNotNull('plan_vence')->whereDate('plan_vence', '<', Carbon::today())->count();

        return view('superadmin.dashboard', compact(
            'totalEmpresas', 'empresasActivas', 'empresasSuspendidas', 'totalUsuarios',
            'planes', 'mrr', 'ventasGlobalMes', 'numVentasMes', 'altas6meses',
            'topEmpresas', 'nombres', 'ultimasEmpresas', 'planesVencidos'
        ));
    }

    public function planes()
    {
        $catalogo = [
            'basico' => [
                'nombre' => 'Básico',
                'precio' => Empresa::PRECIOS_PLAN['basico'],
                'color' => 'slate',
                'features' => ['1 sucursal', 'Hasta 2 usuarios', 'POS e inventario', 'Reportes básicos', 'Soporte por correo'],
            ],
            'pro' => [
                'nombre' => 'Pro',
                'precio' => Empresa::PRECIOS_PLAN['pro'],
                'color' => 'teal',
                'features' => ['1 sucursal', 'Hasta 8 usuarios', 'Cuentas por cobrar/pagar', 'Cotizaciones y caja', 'Soporte prioritario'],
            ],
            'premium' => [
                'nombre' => 'Premium',
                'precio' => Empresa::PRECIOS_PLAN['premium'],
                'color' => 'navy',
                'features' => ['Multi-sucursal', 'Usuarios ilimitados', 'Todos los módulos', 'Reportes avanzados', 'Soporte dedicado 24/7'],
            ],
        ];

        $conteos = Empresa::select('plan', DB::raw('COUNT(*) as total'), DB::raw('SUM(activo) as activas'))
            ->groupBy('plan')->get()->keyBy('plan');

        return view('superadmin.planes', compact('catalogo', 'conteos'));
    }
}
