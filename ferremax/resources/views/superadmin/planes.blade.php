@extends('layouts.superadmin')

@section('titulo', 'Planes y precios')

@section('contenido')
<div class="mb-6">
    <h2 class="text-2xl font-extrabold text-plat">Planes y precios</h2>
    <p class="text-sm text-slate-500">Catálogo de suscripción de la plataforma. El plan de cada ferretería se asigna desde su ficha.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    @foreach ($catalogo as $key => $plan)
        @php
            $fila = $conteos->get($key);
            $total = (int) ($fila->total ?? 0);
            $activas = (int) ($fila->activas ?? 0);
            $destacado = $key === 'pro';
            $borde = $destacado ? 'border-plat-accent ring-2 ring-plat-accent/20' : 'border-slate-200';
        @endphp
        <div class="bg-white rounded-2xl border {{ $borde }} shadow-sm p-6 flex flex-col">
            @if ($destacado)
                <span class="self-start text-[10px] font-bold uppercase tracking-wider bg-plat-accent text-white px-2.5 py-1 rounded-full mb-3">Más popular</span>
            @endif
            <h3 class="text-lg font-extrabold text-plat">{{ $plan['nombre'] }}</h3>
            <div class="mt-2 mb-4">
                <span class="text-4xl font-extrabold text-plat">${{ number_format($plan['precio'], 0) }}</span>
                <span class="text-sm text-slate-400">/ mes</span>
            </div>
            <ul class="space-y-2 mb-6 flex-1">
                @foreach ($plan['features'] as $f)
                    <li class="flex items-start gap-2 text-sm text-slate-600">
                        <svg class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        {{ $f }}
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 pt-4 grid grid-cols-2 gap-2 text-center">
                <div>
                    <p class="text-2xl font-extrabold text-plat">{{ $total }}</p>
                    <p class="text-[11px] text-slate-400 uppercase">Ferreterías</p>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-emerald-600">${{ number_format($activas * $plan['precio'], 0) }}</p>
                    <p class="text-[11px] text-slate-400 uppercase">MRR</p>
                </div>
            </div>
            <a href="{{ route('superadmin.empresas.index', ['plan' => $key]) }}" class="mt-4 text-center text-xs font-bold text-plat-accent hover:underline">Ver ferreterías de este plan →</a>
        </div>
    @endforeach
</div>
@endsection
