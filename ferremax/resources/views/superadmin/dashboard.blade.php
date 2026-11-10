@extends('layouts.superadmin')

@section('titulo', 'Dashboard de plataforma')

@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-2xl font-extrabold text-plat">Visión general de la plataforma</h2>
        <p class="text-sm text-slate-500">Métricas globales de todas las ferreterías suscritas</p>
    </div>
    <a href="{{ route('superadmin.empresas.create') }}" class="inline-flex items-center justify-center gap-2 bg-plat-accent hover:opacity-90 text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-plat-accent/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva ferretería
    </a>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Ferreterías</p>
        <p class="text-3xl font-extrabold text-plat mt-2">{{ $totalEmpresas }}</p>
        <p class="text-xs text-slate-500 mt-1"><span class="text-emerald-600 font-semibold">{{ $empresasActivas }} activas</span> · {{ $empresasSuspendidas }} suspendidas</p>
    </div>
    <div class="bg-gradient-to-br from-plat-accent to-indigo-700 rounded-xl p-5 shadow-sm text-white">
        <p class="text-xs font-bold uppercase tracking-wide text-white/70">MRR estimado</p>
        <p class="text-3xl font-extrabold mt-2">$ {{ number_format($mrr, 0) }}</p>
        <p class="text-xs text-white/70 mt-1">Ingreso recurrente mensual</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Usuarios totales</p>
        <p class="text-3xl font-extrabold text-plat mt-2">{{ $totalUsuarios }}</p>
        <p class="text-xs text-slate-500 mt-1">en todas las ferreterías</p>
    </div>
    <div class="bg-white rounded-xl border {{ $planesVencidos > 0 ? 'border-amber-200' : 'border-slate-200' }} p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Ventas globales (mes)</p>
        <p class="text-2xl font-extrabold text-plat mt-2">{{ number_format($ventasGlobalMes, 2) }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $numVentasMes }} ventas · {{ $planesVencidos }} planes vencidos</p>
    </div>
</div>

<!-- Gráficas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-bold text-plat mb-4">Altas de ferreterías (últimos 6 meses)</h3>
        <canvas id="chartAltas" height="110"></canvas>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-bold text-plat mb-4">Distribución por plan</h3>
        <canvas id="chartPlanes" height="160"></canvas>
    </div>
</div>

<!-- Planes (ingresos) + Top empresas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Ingresos por plan</div>
        <div class="divide-y divide-slate-100">
            @foreach ($planes as $p)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-plat capitalize">{{ $p['plan'] }}</p>
                        <p class="text-[11px] text-slate-400">{{ $p['activas'] }} activas / {{ $p['total'] }} · $ {{ number_format($p['precio'], 0) }}/mes</p>
                    </div>
                    <p class="font-extrabold text-plat-accent">$ {{ number_format($p['ingreso'], 0) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Top ferreterías por facturación (mes)</div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse ($topEmpresas as $t)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('superadmin.empresas.show', $t->empresa_id) }}" class="font-semibold text-plat hover:text-plat-accent">
                                {{ $nombres[$t->empresa_id] ?? 'Empresa #' . $t->empresa_id }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-right font-extrabold text-plat">{{ number_format($t->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="px-5 py-8 text-center text-slate-400">Aún no hay ventas este mes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Últimas empresas -->
<div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Últimas ferreterías registradas</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Ferretería</th>
                    <th class="px-4 py-3 font-bold">Plan</th>
                    <th class="px-4 py-3 font-bold text-center">Usuarios</th>
                    <th class="px-4 py-3 font-bold text-center">Estado</th>
                    <th class="px-4 py-3 font-bold">Registrada</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($ultimasEmpresas as $e)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('superadmin.empresas.show', $e) }}" class="font-semibold text-plat hover:text-plat-accent">{{ $e->nombre }}</a>
                            <span class="block text-[11px] text-slate-400">{{ $e->email ?? ($e->ruc ? 'RUC ' . $e->ruc : '—') }}</span>
                        </td>
                        <td class="px-4 py-3"><span class="text-[11px] font-bold px-2 py-1 rounded-full bg-slate-100 text-slate-600 capitalize">{{ $e->plan }}</span></td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $e->users_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($e->activo)
                                <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Activa</span>
                            @else
                                <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Suspendida</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $e->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('chartAltas'), {
        type: 'bar',
        data: {
            labels: @json($altas6meses->pluck('mes')),
            datasets: [{ data: @json($altas6meses->pluck('total')), backgroundColor: '#6366F1', borderRadius: 6 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('chartPlanes'), {
        type: 'doughnut',
        data: {
            labels: @json(collect($planes)->pluck('plan')),
            datasets: [{ data: @json(collect($planes)->pluck('total')), backgroundColor: ['#94A3B8', '#2A9D8F', '#16344F'] }]
        },
        options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
    });
</script>
@endpush
