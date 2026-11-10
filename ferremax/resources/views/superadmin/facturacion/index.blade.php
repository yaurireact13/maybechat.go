@extends('layouts.superadmin')

@section('titulo', 'Facturación')

@section('contenido')
@php $hoy = \Carbon\Carbon::today(); @endphp

<div class="mb-5">
    <h2 class="text-2xl font-extrabold text-plat">Facturación de suscripciones</h2>
    <p class="text-sm text-slate-500">Cobros de plan, morosidad y vencimientos de toda la plataforma</p>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-plat-accent to-indigo-700 rounded-xl p-5 shadow-sm text-white">
        <p class="text-xs font-bold uppercase tracking-wide text-white/70">Recaudado este mes</p>
        <p class="text-3xl font-extrabold mt-2">$ {{ number_format($recaudadoMes, 0) }}</p>
        <p class="text-xs text-white/70 mt-1">{{ $pagosMes }} pagos</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">MRR</p>
        <p class="text-3xl font-extrabold text-plat mt-2">$ {{ number_format($mrr, 0) }}</p>
        <p class="text-xs text-slate-500 mt-1">ingreso recurrente</p>
    </div>
    <div class="bg-white rounded-xl border {{ $morosos->count() ? 'border-red-200' : 'border-slate-200' }} p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-red-400">Morosos</p>
        <p class="text-3xl font-extrabold {{ $morosos->count() ? 'text-red-600' : 'text-plat' }} mt-2">{{ $morosos->count() }}</p>
        <p class="text-xs text-slate-500 mt-1">con plan vencido</p>
    </div>
    <div class="bg-white rounded-xl border {{ $porVencer->count() ? 'border-amber-200' : 'border-slate-200' }} p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-amber-500">Por vencer (15 días)</p>
        <p class="text-3xl font-extrabold text-plat mt-2">{{ $porVencer->count() }}</p>
        <p class="text-xs text-slate-500 mt-1">requieren renovación</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    <!-- Morosos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-red-500"></span> Ferreterías morosas
        </div>
        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
            @forelse ($morosos as $e)
                <a href="{{ route('superadmin.empresas.show', $e) }}" class="px-5 py-3 flex items-center justify-between hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-plat">{{ $e->nombre }}</p>
                        <p class="text-[11px] text-red-500">Venció {{ $e->plan_vence->format('d/m/Y') }} · hace {{ $hoy->diffInDays($e->plan_vence) }} días</p>
                    </div>
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full {{ $e->activo ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">{{ $e->activo ? 'Activa' : 'Suspendida' }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-slate-400 text-sm">Sin morosos. 🎉</p>
            @endforelse
        </div>
    </div>

    <!-- Por vencer -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Próximos a vencer
        </div>
        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
            @forelse ($porVencer as $e)
                <a href="{{ route('superadmin.empresas.show', $e) }}" class="px-5 py-3 flex items-center justify-between hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-plat">{{ $e->nombre }}</p>
                        <p class="text-[11px] text-slate-400 capitalize">Plan {{ $e->plan }} · vence {{ $e->plan_vence->format('d/m/Y') }}</p>
                    </div>
                    <span class="text-[11px] font-bold text-amber-600">en {{ $hoy->diffInDays($e->plan_vence) }} d</span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-slate-400 text-sm">Nada por vencer en 15 días.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Historial de pagos -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 font-bold text-plat">Últimos pagos de suscripción</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Ferretería</th>
                    <th class="px-4 py-3 font-bold">Plan</th>
                    <th class="px-4 py-3 font-bold">Periodo</th>
                    <th class="px-4 py-3 font-bold">Método</th>
                    <th class="px-4 py-3 font-bold">Registró</th>
                    <th class="px-5 py-3 font-bold text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pagos as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('superadmin.empresas.show', $p->empresa_id) }}" class="font-semibold text-plat hover:text-plat-accent">{{ $p->empresa->nombre ?? '—' }}</a>
                            <span class="block text-[11px] text-slate-400">{{ $p->fecha_pago->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $p->plan }}</td>
                        <td class="px-4 py-3 text-slate-500 text-[12px]">{{ $p->periodo_desde->format('d/m/y') }} → {{ $p->periodo_hasta->format('d/m/y') }}</td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $p->metodo }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $p->registrador->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-bold text-plat">$ {{ number_format($p->monto, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">Aún no se han registrado pagos de suscripción.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $pagos->links() }}</div>
@endsection
