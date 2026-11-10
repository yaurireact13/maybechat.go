@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Reportes</h1>
        <p class="text-sm text-slate-500">Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</p>
    </div>
    <a href="{{ route('reportes.exportar', request()->only(['desde', 'hasta'])) }}"
       class="inline-flex items-center justify-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-bold px-4 py-2.5 rounded-lg transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
        Exportar CSV
    </a>
</div>

<!-- Rango de fechas -->
<form method="GET" action="{{ route('reportes.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-col sm:flex-row gap-3 sm:items-end">
    <div>
        <label class="block text-xs font-bold text-slate-500 mb-1">Desde</label>
        <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"
               class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"
               class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <button type="submit" class="bg-navy hover:bg-navy-light text-white text-sm font-bold px-6 py-2 rounded-lg transition">Aplicar</button>
    <div class="flex gap-2 sm:ml-auto">
        <a href="{{ route('reportes.index', ['desde' => now()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}" class="text-xs font-bold text-teal-brand bg-teal-brand/10 px-3 py-2 rounded-lg hover:bg-teal-brand/20 transition">Hoy</a>
        <a href="{{ route('reportes.index', ['desde' => now()->startOfWeek()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}" class="text-xs font-bold text-teal-brand bg-teal-brand/10 px-3 py-2 rounded-lg hover:bg-teal-brand/20 transition">Semana</a>
        <a href="{{ route('reportes.index', ['desde' => now()->startOfMonth()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}" class="text-xs font-bold text-teal-brand bg-teal-brand/10 px-3 py-2 rounded-lg hover:bg-teal-brand/20 transition">Mes</a>
        <a href="{{ route('reportes.index', ['desde' => now()->startOfYear()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}" class="text-xs font-bold text-teal-brand bg-teal-brand/10 px-3 py-2 rounded-lg hover:bg-teal-brand/20 transition">Año</a>
    </div>
</form>

<!-- KPIs -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Ventas totales</p>
        <p class="text-xl sm:text-2xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($totales->total, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Utilidad estimada</p>
        <p class="text-xl sm:text-2xl font-extrabold {{ $utilidad >= 0 ? 'text-emerald-600' : 'text-red-500' }} mt-1">{{ $moneda }} {{ number_format($utilidad, 2) }}</p>
        <p class="text-[10px] text-slate-400">según costo actual de productos</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">N° de ventas</p>
        <p class="text-xl sm:text-2xl font-extrabold text-navy mt-1">{{ number_format($totales->cantidad) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Ticket promedio</p>
        <p class="text-xl sm:text-2xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($totales->ticket, 2) }}</p>
    </div>
</div>

<!-- Gráficas -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-5">
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-bold text-navy text-sm mb-4">Ventas por día</h3>
        <div class="h-64"><canvas id="chartDias"></canvas></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-bold text-navy text-sm mb-4">Por método de pago</h3>
        <div class="h-64 flex items-center justify-center"><canvas id="chartPago"></canvas></div>
    </div>
</div>

<!-- Tops -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 pt-5 pb-3"><h3 class="font-bold text-navy text-sm">Top 10 productos</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-y border-slate-100 bg-slate-50">
                        <th class="px-5 py-2.5 font-bold">Producto</th>
                        <th class="px-3 py-2.5 font-bold text-center">Cant.</th>
                        <th class="px-3 py-2.5 font-bold text-right">Vendido</th>
                        <th class="px-5 py-2.5 font-bold text-right hidden sm:table-cell">Utilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($topProductos as $tp)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-2.5">
                                <p class="font-semibold text-slate-700 text-xs">{{ $tp->nombre }}</p>
                                <p class="text-[10px] font-mono text-slate-400">{{ $tp->codigo }}</p>
                            </td>
                            <td class="px-3 py-2.5 text-center font-bold text-slate-600">{{ $tp->cantidad + 0 }}</td>
                            <td class="px-3 py-2.5 text-right font-bold text-navy">{{ $moneda }} {{ number_format($tp->total, 2) }}</td>
                            <td class="px-5 py-2.5 text-right font-semibold {{ $tp->utilidad >= 0 ? 'text-emerald-600' : 'text-red-500' }} hidden sm:table-cell">{{ $moneda }} {{ number_format($tp->utilidad, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">Sin datos en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 pt-5 pb-3"><h3 class="font-bold text-navy text-sm">Top 10 clientes</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-y border-slate-100 bg-slate-50">
                        <th class="px-5 py-2.5 font-bold">Cliente</th>
                        <th class="px-3 py-2.5 font-bold text-center">Compras</th>
                        <th class="px-5 py-2.5 font-bold text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($topClientes as $tc)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-2.5 font-semibold text-slate-700 text-xs">{{ $tc->nombre }}</td>
                            <td class="px-3 py-2.5 text-center font-bold text-slate-600">{{ $tc->compras }}</td>
                            <td class="px-5 py-2.5 text-right font-bold text-navy">{{ $moneda }} {{ number_format($tc->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">Sin ventas con cliente identificado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.color = '#64748B';

    new Chart(document.getElementById('chartDias'), {
        type: 'bar',
        data: {
            labels: @json($porDia->pluck('dia')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
            datasets: [{
                data: @json($porDia->pluck('total')->map(fn($t) => (float) $t)),
                backgroundColor: '#2A9D8F',
                borderRadius: 5,
                maxBarThickness: 28,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('chartPago'), {
        type: 'doughnut',
        data: {
            labels: @json($porPago->pluck('metodo_pago')->map(fn($m) => ucfirst($m))),
            datasets: [{
                data: @json($porPago->pluck('total')->map(fn($t) => (float) $t)),
                backgroundColor: ['#2A9D8F', '#16344F', '#A8D582', '#5EAAA8', '#94A3B8', '#CBD5E1'],
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: { legend: { position: window.innerWidth < 640 ? 'bottom' : 'right', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
    });
</script>
@endpush
