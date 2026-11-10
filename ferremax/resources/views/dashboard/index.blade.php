@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

@if (session('bienvenida') || $totalProductos === 0)
    <!-- Bienvenida / primeros pasos -->
    <div class="mb-6 bg-navy rounded-2xl p-6 sm:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-teal-brand opacity-20"></div>
        <div class="relative z-10">
            <h2 class="text-xl sm:text-2xl font-extrabold">¡Bienvenido a FerreMax, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-slate-300 text-sm mt-1 mb-5">Tu ferretería ya está lista. Sigue estos pasos para empezar a vender:</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <a href="{{ route('configuracion.index') }}" class="bg-navy-light hover:bg-teal-brand/20 border border-white/10 rounded-xl p-4 transition group">
                    <p class="text-2xl mb-1">⚙️</p>
                    <p class="text-sm font-bold group-hover:text-lime-brand transition">1. Revisa tu configuración</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Datos de empresa, moneda e impuesto.</p>
                </a>
                <a href="{{ route('productos.create') }}" class="bg-navy-light hover:bg-teal-brand/20 border border-white/10 rounded-xl p-4 transition group">
                    <p class="text-2xl mb-1">📦</p>
                    <p class="text-sm font-bold group-hover:text-lime-brand transition">2. Registra tus productos</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Con precios, stock y categorías.</p>
                </a>
                <a href="{{ route('proveedores.index') }}" class="bg-navy-light hover:bg-teal-brand/20 border border-white/10 rounded-xl p-4 transition group">
                    <p class="text-2xl mb-1">🚚</p>
                    <p class="text-sm font-bold group-hover:text-lime-brand transition">3. Agrega proveedores</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Para registrar compras de mercadería.</p>
                </a>
                <a href="{{ route('pos.index') }}" class="bg-navy-light hover:bg-teal-brand/20 border border-white/10 rounded-xl p-4 transition group">
                    <p class="text-2xl mb-1">💰</p>
                    <p class="text-sm font-bold group-hover:text-lime-brand transition">4. ¡Haz tu primera venta!</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Abre caja y usa el punto de venta.</p>
                </a>
            </div>
        </div>
    </div>
@endif

<!-- Encabezado -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Dashboard</h1>
        <p class="text-sm text-slate-500">Resumen de {{ auth()->user()->empresa->nombre ?? 'tu ferretería' }} — {{ now()->locale('es')->isoFormat('dddd D [de] MMMM, YYYY') }}</p>
    </div>
    <a href="{{ route('pos.index') }}"
       class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-5 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva Venta
    </a>
</div>

<!-- KPIs -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <!-- Ventas de Hoy -->
    <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-teal-brand/20 bg-gradient-to-br from-teal-brand to-teal-dark text-white">
        <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/10"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-white/80">Ventas de Hoy</p>
                <span class="w-9 h-9 rounded-lg bg-white/20 text-white flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ $moneda }} {{ number_format($ventasHoy, 2) }}</p>
            <p class="text-xs text-white/70 mt-1">{{ $numVentasHoy }} {{ $numVentasHoy == 1 ? 'venta registrada' : 'ventas registradas' }}</p>
        </div>
    </div>

    <!-- Ventas del Mes -->
    <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-lime-brand/30 bg-gradient-to-br from-lime-brand to-[#7cb342] text-navy">
        <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/20"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-navy/70">Ventas del Mes</p>
                <span class="w-9 h-9 rounded-lg bg-white/40 text-navy flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ $moneda }} {{ number_format($ventasMes, 2) }}</p>
            <p class="text-xs text-navy/60 mt-1 capitalize">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
        </div>
    </div>

    <!-- Productos / Clientes -->
    <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg shadow-navy/25 bg-gradient-to-br from-navy to-navy-light text-white">
        <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-teal-brand/25"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-white/70">Productos / Clientes</p>
                <span class="w-9 h-9 rounded-lg bg-white/15 text-white flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ number_format($totalProductos) }} <span class="text-lg text-white/50 font-bold">/ {{ number_format($totalClientes) }}</span></p>
            <p class="text-xs text-white/60 mt-1">Inventario: {{ $moneda }} {{ number_format($valorInventario, 2) }}</p>
        </div>
    </div>

    <!-- Stock Bajo -->
    <div class="relative overflow-hidden rounded-2xl p-5 shadow-lg {{ $stockBajo > 0 ? 'shadow-red-500/30 bg-gradient-to-br from-red-500 to-rose-600 text-white' : 'shadow-emerald-500/20 bg-gradient-to-br from-emerald-500 to-teal-600 text-white' }}">
        <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/15"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-white/80">Stock Bajo</p>
                <span class="w-9 h-9 rounded-lg bg-white/20 text-white flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold mt-2">{{ $stockBajo }}</p>
            <p class="text-xs text-white/70 mt-1">{{ $stockBajo == 1 ? 'producto requiere' : 'productos requieren' }} reposición</p>
        </div>
    </div>
</div>

<!-- Cuentas por cobrar / pagar -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <a href="{{ route('cuentas.cobrar') }}" class="group relative overflow-hidden rounded-2xl p-5 shadow-md bg-gradient-to-br from-teal-brand/10 to-teal-brand/5 border border-teal-brand/20 hover:shadow-lg hover:-translate-y-0.5 transition flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-teal-dark/70">Por Cobrar</p>
            <p class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">{{ $moneda }} {{ number_format($porCobrar, 2) }}</p>
            <p class="text-xs mt-1 {{ $cxcVencido > 0 ? 'text-red-500 font-semibold' : 'text-slate-500' }}">
                {{ $cxcVencido > 0 ? $moneda . ' ' . number_format($cxcVencido, 2) . ' vencido' : 'Sin saldos vencidos' }}
            </p>
        </div>
        <span class="w-11 h-11 rounded-xl bg-teal-brand text-white flex items-center justify-center shadow-lg shadow-teal-brand/30 group-hover:scale-110 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
        </span>
    </a>
    <a href="{{ route('cuentas.pagar') }}" class="group relative overflow-hidden rounded-2xl p-5 shadow-md bg-gradient-to-br from-navy/10 to-navy/5 border border-navy/20 hover:shadow-lg hover:-translate-y-0.5 transition flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-navy/70">Por Pagar</p>
            <p class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">{{ $moneda }} {{ number_format($porPagar, 2) }}</p>
            <p class="text-xs mt-1 {{ $cxpVencido > 0 ? 'text-red-500 font-semibold' : 'text-slate-500' }}">
                {{ $cxpVencido > 0 ? $moneda . ' ' . number_format($cxpVencido, 2) . ' vencido' : 'Sin saldos vencidos' }}
            </p>
        </div>
        <span class="w-11 h-11 rounded-xl bg-navy text-white flex items-center justify-center shadow-lg shadow-navy/30 group-hover:scale-110 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" /></svg>
        </span>
    </a>
</div>

<!-- ===================== GRÁFICOS ESTADÍSTICOS ===================== -->

<!-- Fila superior: Ingresos vs Egresos + Ventas por categoría -->
<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 mb-4">
    <!-- 1) Ingresos vs Egresos (barras agrupadas) -->
    <div class="xl:col-span-3 relative overflow-hidden rounded-2xl p-5 shadow-md bg-gradient-to-br from-white to-slate-50 border border-slate-200">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-bold text-navy text-sm flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-teal-brand"></span> Ingresos vs Egresos
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Ventas vs compras — últimos 6 meses ({{ $moneda }})</p>
            </div>
            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-teal-brand/10 text-teal-dark">6 M</span>
        </div>
        <div class="relative w-full h-52 sm:h-64 lg:h-72 xl:h-80"><canvas id="chartIngresosEgresos"></canvas></div>
    </div>

    <!-- 2) Ventas por categoría (polar) -->
    <div class="xl:col-span-2 relative overflow-hidden rounded-2xl p-5 shadow-md bg-gradient-to-br from-white to-slate-50 border border-slate-200">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-bold text-navy text-sm flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-lime-brand"></span> Ventas por categoría
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Distribución del mes ({{ $moneda }})</p>
            </div>
            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-lime-brand/20 text-lime-700">MES</span>
        </div>
        @if ($ventasPorCategoria->count())
            <div class="relative w-full h-52 sm:h-64 lg:h-72 xl:h-80 flex items-center justify-center"><canvas id="chartCategoria"></canvas></div>
        @else
            <div class="h-52 sm:h-64 lg:h-72 xl:h-80 flex items-center justify-center text-sm text-slate-400">Sin ventas este mes.</div>
        @endif
    </div>
</div>

<!-- Fila inferior: Ventas por hora + Estado de cartera -->
<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 mb-6">
    <!-- 3) Ventas por hora (línea de área) -->
    <div class="xl:col-span-3 relative overflow-hidden rounded-2xl p-5 shadow-md bg-gradient-to-br from-white to-slate-50 border border-slate-200">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-bold text-navy text-sm flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-navy"></span> Ventas por hora del día
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Horas pico — últimos 30 días ({{ $moneda }})</p>
            </div>
            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-navy/10 text-navy">30 D</span>
        </div>
        <div class="relative w-full h-52 sm:h-64 lg:h-72"><canvas id="chartHoras"></canvas></div>
    </div>

    <!-- 4) Estado de cartera (barras apiladas horizontales) -->
    <div class="xl:col-span-2 relative overflow-hidden rounded-2xl p-5 shadow-md bg-gradient-to-br from-white to-slate-50 border border-slate-200">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-bold text-navy text-sm flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-teal-dark"></span> Estado de cartera
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Vigente vs vencido ({{ $moneda }})</p>
            </div>
        </div>
        <div class="relative w-full h-52 sm:h-64 lg:h-72"><canvas id="chartCartera"></canvas></div>
        <div class="flex items-center justify-center gap-4 mt-2 text-[11px] font-semibold">
            <span class="flex items-center gap-1.5 text-slate-500"><span class="w-3 h-3 rounded-sm bg-teal-brand"></span>Vigente</span>
            <span class="flex items-center gap-1.5 text-slate-500"><span class="w-3 h-3 rounded-sm bg-red-500"></span>Vencido</span>
        </div>
    </div>
</div>

<!-- Alertas de stock bajo -->
<div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mb-6">
    <div class="flex items-center justify-between mb-1">
        <h3 class="font-bold text-navy text-sm">Alertas de stock bajo</h3>
        <a href="{{ route('inventario.index') }}" class="text-xs font-semibold text-teal-brand hover:text-teal-dark">Ver todo →</a>
    </div>
    <p class="text-xs text-slate-400 mb-4">Stock actual vs. mínimo</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5">
        @forelse ($productosStockBajo as $p)
            <div class="flex items-center justify-between bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                <div class="min-w-0 pr-2">
                    <p class="text-xs font-semibold text-slate-700 truncate">{{ $p->nombre }}</p>
                    <p class="text-[10px] text-slate-400">{{ $p->codigo }}</p>
                </div>
                <span class="text-xs font-bold text-red-500 shrink-0">{{ $p->stock + 0 }} / {{ $p->stock_minimo + 0 }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">Todo el stock está saludable ✓</p>
        @endforelse
    </div>
</div>

<!-- Últimas ventas + movimientos -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 pt-5 pb-3">
            <h3 class="font-bold text-navy text-sm">Últimas ventas</h3>
            <a href="{{ route('ventas.index') }}" class="text-xs font-semibold text-teal-brand hover:text-teal-dark">Ver todas →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-y border-slate-100 bg-slate-50">
                        <th class="px-5 py-2.5 font-bold hidden sm:table-cell">N°</th>
                        <th class="px-3 py-2.5 font-bold">Cliente</th>
                        <th class="px-3 py-2.5 font-bold hidden md:table-cell">Fecha</th>
                        <th class="px-3 py-2.5 font-bold">Pago</th>
                        <th class="px-5 py-2.5 font-bold text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ultimasVentas as $v)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs text-slate-500 hidden sm:table-cell">{{ $v->numero }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-700">{{ $v->cliente->nombre ?? 'Cliente Varios' }}</td>
                            <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $v->fecha->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-3">
                                <span class="text-[11px] font-bold px-2 py-1 rounded-full capitalize
                                    {{ $v->metodo_pago === 'efectivo' ? 'bg-lime-brand/20 text-lime-700' : 'bg-teal-brand/10 text-teal-dark' }}">
                                    {{ $v->metodo_pago }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-navy">{{ $moneda }} {{ number_format($v->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-6 text-center text-slate-400">Aún no hay ventas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-bold text-navy text-sm mb-4">Movimientos recientes</h3>
        <div class="space-y-3">
            @forelse ($ultimosMovimientos as $m)
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                        {{ $m->tipo === 'entrada' ? 'bg-lime-brand/20 text-lime-700' : 'bg-red-100 text-red-500' }}">
                        @if ($m->tipo === 'entrada')
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-700 truncate">{{ $m->producto->nombre ?? '—' }}</p>
                        <p class="text-[11px] text-slate-400 capitalize">{{ $m->tipo }} de {{ $m->cantidad + 0 }} — {{ $m->fecha->locale('es')->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400">Sin movimientos recientes.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const colores = { navy: '#16344F', navyLight: '#1E4366', teal: '#2A9D8F', tealDark: '#21867A', tealLight: '#5EAAA8', lime: '#A8D582', limeDark: '#7cb342' };
    const paleta = ['#2A9D8F', '#16344F', '#A8D582', '#5EAAA8', '#F4A261', '#E76F51', '#8E7DBE'];
    const moneda = @json($moneda);
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.color = '#64748B';
    Chart.defaults.font.size = 11;
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.resizeDelay = 120;

    const _charts = [];

    // Formato de moneda corto: 1.2K / 70.8K / 1.1M
    const fmtCorto = (v) => {
        const n = Number(v) || 0;
        if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
        if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
        return n.toFixed(0);
    };
    const fmtMoneda = (v) => moneda + ' ' + Number(v || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const gradV = (chart, from, to) => {
        const { ctx, chartArea } = chart;
        if (!chartArea) return from;
        const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, from); g.addColorStop(1, to);
        return g;
    };
    const gradH = (chart, from, to) => {
        const { ctx, chartArea } = chart;
        if (!chartArea) return from;
        const g = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
        g.addColorStop(0, from); g.addColorStop(1, to);
        return g;
    };

    const tooltipBase = {
        backgroundColor: '#16344F', padding: 12, cornerRadius: 10,
        titleFont: { weight: '700', size: 12 }, bodyFont: { size: 12 },
        displayColors: true, usePointStyle: true, boxPadding: 5,
    };

    // ============ 1) Ingresos vs Egresos — barras agrupadas ============
    _charts.push(new Chart(document.getElementById('chartIngresosEgresos'), {
        type: 'bar',
        data: {
            labels: @json($ingresosEgresos->pluck('mes')),
            datasets: [
                {
                    label: 'Ingresos',
                    data: @json($ingresosEgresos->pluck('ingresos')),
                    backgroundColor: (c) => gradV(c.chart, '#34c0b0', '#21867A'),
                    borderRadius: 7, borderSkipped: false, maxBarThickness: 30,
                },
                {
                    label: 'Egresos',
                    data: @json($ingresosEgresos->pluck('egresos')),
                    backgroundColor: (c) => gradV(c.chart, '#3a6a94', '#16344F'),
                    borderRadius: 7, borderSkipped: false, maxBarThickness: 30,
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', align: 'end', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle', padding: 14, font: { size: 11, weight: '600' } } },
                tooltip: { ...tooltipBase, callbacks: { label: (c) => c.dataset.label + ': ' + fmtMoneda(c.parsed.y) } }
            },
            scales: {
                y: { beginAtZero: true, border: { display: false }, grid: { color: '#F1F5F9' }, ticks: { callback: fmtCorto } },
                x: { border: { display: false }, grid: { display: false } }
            }
        }
    }));

    // ============ 2) Ventas por categoría — polar area ============
    @if ($ventasPorCategoria->count())
    _charts.push(new Chart(document.getElementById('chartCategoria'), {
        type: 'polarArea',
        data: {
            labels: @json($ventasPorCategoria->pluck('categoria')),
            datasets: [{
                data: @json($ventasPorCategoria->pluck('total')),
                backgroundColor: paleta.map(c => c + 'cc'),
                borderColor: '#fff',
                borderWidth: 2,
                hoverBackgroundColor: paleta,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle', padding: 10, font: { size: 10 } } },
                tooltip: { ...tooltipBase, callbacks: { label: (c) => ' ' + fmtMoneda(c.parsed.r ?? c.parsed) } }
            },
            scales: {
                r: { grid: { color: '#E2E8F0' }, ticks: { display: false, backdropColor: 'transparent' } }
            }
        }
    }));
    @endif

    // ============ 3) Ventas por hora del día — línea de área ============
    _charts.push(new Chart(document.getElementById('chartHoras'), {
        type: 'line',
        data: {
            labels: @json($ventasPorHora->pluck('hora')),
            datasets: [{
                label: 'Ventas',
                data: @json($ventasPorHora->pluck('total')),
                borderColor: colores.navy,
                borderWidth: 3,
                fill: true,
                backgroundColor: (c) => gradV(c.chart, 'rgba(22,52,79,0.25)', 'rgba(22,52,79,0.00)'),
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: colores.navy,
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltipBase, displayColors: false, callbacks: { title: (c) => 'Hora ' + c[0].label, label: (c) => fmtMoneda(c.parsed.y) } }
            },
            scales: {
                y: { beginAtZero: true, border: { display: false }, grid: { color: '#F1F5F9' }, ticks: { callback: fmtCorto } },
                x: { border: { display: false }, grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12 } }
            }
        }
    }));

    // ============ 4) Estado de cartera — barras apiladas horizontales ============
    @php $c = $cartera; @endphp
    _charts.push(new Chart(document.getElementById('chartCartera'), {
        type: 'bar',
        data: {
            labels: ['Por Cobrar', 'Por Pagar'],
            datasets: [
                {
                    label: 'Vigente',
                    data: [{{ $c['cobrar_vigente'] }}, {{ $c['pagar_vigente'] }}],
                    backgroundColor: (ctx) => gradH(ctx.chart, '#34c0b0', '#2A9D8F'),
                    borderRadius: 6, borderSkipped: false, maxBarThickness: 46,
                },
                {
                    label: 'Vencido',
                    data: [{{ $c['cobrar_vencido'] }}, {{ $c['pagar_vencido'] }}],
                    backgroundColor: (ctx) => gradH(ctx.chart, '#f87171', '#dc2626'),
                    borderRadius: 6, borderSkipped: false, maxBarThickness: 46,
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltipBase, callbacks: { label: (c) => c.dataset.label + ': ' + fmtMoneda(c.parsed.x) } }
            },
            scales: {
                x: { stacked: true, beginAtZero: true, border: { display: false }, grid: { color: '#F1F5F9' }, ticks: { callback: fmtCorto } },
                y: { stacked: true, border: { display: false }, grid: { display: false }, ticks: { font: { weight: '700' } } }
            }
        }
    }));

    // Redibujar al cambiar el tamaño de la ventana (debounced)
    let _rt;
    window.addEventListener('resize', () => {
        clearTimeout(_rt);
        _rt = setTimeout(() => _charts.forEach(ch => ch.resize()), 150);
    });
</script>
@endpush
