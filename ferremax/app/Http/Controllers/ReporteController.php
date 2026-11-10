<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    private function rango(Request $request): array
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde)->startOfDay() : now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta)->endOfDay() : now()->endOfDay();

        return [$desde, $hasta];
    }

    public function index(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);

        $base = Venta::where('estado', 'completada')->whereBetween('fecha', [$desde, $hasta]);

        // KPIs
        $totales = (clone $base)->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total, COALESCE(AVG(total), 0) as ticket')->first();

        $utilidad = VentaDetalle::join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->whereHas('venta', fn ($q) => $q->where('estado', 'completada')->whereBetween('fecha', [$desde, $hasta]))
            ->selectRaw('COALESCE(SUM(venta_detalles.subtotal - (venta_detalles.cantidad * productos.precio_compra)), 0) as utilidad')
            ->value('utilidad');

        // Ventas por día
        $porDia = (clone $base)->selectRaw('DATE(fecha) as dia, SUM(total) as total, COUNT(*) as cantidad')
            ->groupBy('dia')->orderBy('dia')->get();

        // Top productos
        $topProductos = VentaDetalle::join('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->whereHas('venta', fn ($q) => $q->where('estado', 'completada')->whereBetween('fecha', [$desde, $hasta]))
            ->selectRaw('productos.nombre, productos.codigo,
                SUM(venta_detalles.cantidad) as cantidad,
                SUM(venta_detalles.subtotal) as total,
                SUM(venta_detalles.subtotal - (venta_detalles.cantidad * productos.precio_compra)) as utilidad')
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->orderByDesc('total')->limit(10)->get();

        // Top clientes
        $topClientes = (clone $base)->whereNotNull('cliente_id')
            ->join('clientes', 'clientes.id', '=', 'ventas.cliente_id')
            ->selectRaw('clientes.nombre, COUNT(*) as compras, SUM(ventas.total) as total')
            ->groupBy('clientes.id', 'clientes.nombre')
            ->orderByDesc('total')->limit(10)->get();

        // Por método de pago
        $porPago = (clone $base)->selectRaw('metodo_pago, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('metodo_pago')->orderByDesc('total')->get();

        return view('reportes.index', compact('desde', 'hasta', 'totales', 'utilidad', 'porDia', 'topProductos', 'topClientes', 'porPago'));
    }

    public function exportar(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        $ventas = Venta::with(['cliente', 'user'])
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->get();

        $nombre = 'ventas_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($ventas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($out, ['Número', 'Fecha', 'Cliente', 'Documento', 'Comprobante', 'Método de pago', 'Estado', 'Subtotal', 'IGV', 'Descuento', 'Total', 'Vendedor'], ';');
            foreach ($ventas as $v) {
                fputcsv($out, [
                    $v->numero,
                    $v->fecha->format('d/m/Y H:i'),
                    $v->cliente->nombre ?? 'Cliente Varios',
                    $v->cliente->numero_documento ?? '',
                    $v->tipo_comprobante,
                    $v->metodo_pago,
                    $v->estado,
                    number_format($v->subtotal, 2, '.', ''),
                    number_format($v->impuesto, 2, '.', ''),
                    number_format($v->descuento, 2, '.', ''),
                    number_format($v->total, 2, '.', ''),
                    $v->user->name ?? '',
                ], ';');
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
