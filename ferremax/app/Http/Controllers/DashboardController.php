<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();

        // KPIs
        $ventasHoy = Venta::where('estado', 'completada')->whereDate('fecha', $hoy)->sum('total');
        $numVentasHoy = Venta::where('estado', 'completada')->whereDate('fecha', $hoy)->count();
        $ventasMes = Venta::where('estado', 'completada')->where('fecha', '>=', $inicioMes)->sum('total');
        $totalProductos = Producto::where('activo', true)->count();
        $totalClientes = Cliente::where('activo', true)->count();
        $stockBajo = Producto::where('activo', true)->stockBajo()->count();
        $valorInventario = Producto::where('activo', true)->sum(DB::raw('stock * precio_compra'));

        // Cuentas por cobrar / pagar
        $cxcBase = Venta::where('metodo_pago', 'credito')->where('estado', '!=', 'anulada')->where('saldo', '>', 0);
        $porCobrar = (clone $cxcBase)->sum('saldo');
        $cxcVencido = (clone $cxcBase)->whereDate('fecha_vencimiento', '<', $hoy)->sum('saldo');
        $cxpBase = Compra::where('a_credito', true)->where('estado', '!=', 'anulada')->where('saldo', '>', 0);
        $porPagar = (clone $cxpBase)->sum('saldo');
        $cxpVencido = (clone $cxpBase)->whereDate('fecha_vencimiento', '<', $hoy)->sum('saldo');

        // ===== GRÁFICO 1: Ingresos (ventas) vs Egresos (compras) — últimos 6 meses =====
        $ingresosEgresos = collect(range(5, 0))->map(function ($m) {
            $inicio = Carbon::now()->subMonths($m)->startOfMonth();
            $fin = Carbon::now()->subMonths($m)->endOfMonth();

            return [
                'mes' => $inicio->locale('es')->isoFormat('MMM'),
                'ingresos' => (float) Venta::where('estado', 'completada')
                    ->whereBetween('fecha', [$inicio, $fin])->sum('total'),
                'egresos' => (float) Compra::where('estado', '!=', 'anulada')
                    ->whereBetween('fecha', [$inicio, $fin])->sum('total'),
            ];
        });

        // ===== GRÁFICO 2: Ventas por categoría de producto — mes actual =====
        $ventasPorCategoria = VentaDetalle::select('categorias.nombre as categoria', DB::raw('SUM(venta_detalles.subtotal) as total'))
            ->join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->whereHas('venta', fn ($q) => $q->where('estado', 'completada')->where('fecha', '>=', $inicioMes))
            ->groupBy('categorias.nombre')
            ->orderByDesc('total')
            ->limit(7)
            ->get()
            ->map(fn ($r) => ['categoria' => $r->categoria ?: 'Sin categoría', 'total' => (float) $r->total]);

        // ===== GRÁFICO 3: Ventas por hora del día — últimos 30 días =====
        $inicio30 = Carbon::now()->subDays(30)->startOfDay();
        $ventasHoraRaw = Venta::select(DB::raw('HOUR(fecha) as hora'), DB::raw('SUM(total) as total'))
            ->where('estado', 'completada')
            ->where('fecha', '>=', $inicio30)
            ->groupBy(DB::raw('HOUR(fecha)'))
            ->pluck('total', 'hora');
        // Horario comercial 7:00 a 21:00
        $ventasPorHora = collect(range(7, 21))->map(fn ($h) => [
            'hora' => sprintf('%02d:00', $h),
            'total' => (float) ($ventasHoraRaw[$h] ?? 0),
        ]);

        // ===== GRÁFICO 4: Estado de cartera (vigente vs vencido) =====
        $cartera = [
            'cobrar_vigente' => max(0, (float) $porCobrar - (float) $cxcVencido),
            'cobrar_vencido' => (float) $cxcVencido,
            'pagar_vigente' => max(0, (float) $porPagar - (float) $cxpVencido),
            'pagar_vencido' => (float) $cxpVencido,
        ];

        // Top 5 productos más vendidos del mes (tabla resumen)
        $topProductos = VentaDetalle::select('producto_id', DB::raw('SUM(cantidad) as cantidad'), DB::raw('SUM(subtotal) as total'))
            ->whereHas('venta', fn ($q) => $q->where('estado', 'completada')->where('fecha', '>=', $inicioMes))
            ->groupBy('producto_id')
            ->orderByDesc('total')
            ->with('producto')
            ->limit(5)
            ->get();

        // Últimas ventas
        $ultimasVentas = Venta::with('cliente')->latest('fecha')->limit(8)->get();

        // Productos con stock bajo
        $productosStockBajo = Producto::where('activo', true)->stockBajo()->orderBy('stock')->limit(6)->get();

        // Últimos movimientos
        $ultimosMovimientos = MovimientoInventario::with('producto')->latest('fecha')->limit(6)->get();

        return view('dashboard.index', compact(
            'ventasHoy', 'numVentasHoy', 'ventasMes', 'totalProductos', 'totalClientes',
            'stockBajo', 'valorInventario', 'ingresosEgresos', 'ventasPorCategoria',
            'ventasPorHora', 'cartera', 'topProductos',
            'ultimasVentas', 'productosStockBajo', 'ultimosMovimientos',
            'porCobrar', 'cxcVencido', 'porPagar', 'cxpVencido'
        ));
    }
}
