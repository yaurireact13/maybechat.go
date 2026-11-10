@extends('layouts.app')

@section('titulo', 'Compras')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Compras</h1>
        <p class="text-sm text-slate-500">
            {{ $resumen->cantidad }} {{ $resumen->cantidad == 1 ? 'compra recibida' : 'compras recibidas' }} por
            <b class="text-navy">{{ $moneda }} {{ number_format($resumen->total, 2) }}</b> según el filtro
        </p>
    </div>
    <a href="{{ route('compras.create') }}"
       class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva Compra
    </a>
</div>

<!-- Filtros -->
<form method="GET" action="{{ route('compras.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
    <div class="lg:col-span-2 relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="N° de compra, proveedor o RUC"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <input type="date" name="desde" value="{{ request('desde') }}" title="Desde"
           class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    <input type="date" name="hasta" value="{{ request('hasta') }}" title="Hasta"
           class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    <select name="estado" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los estados</option>
        <option value="recibida" @selected(request('estado') === 'recibida')>Recibidas</option>
        <option value="anulada" @selected(request('estado') === 'anulada')>Anuladas</option>
    </select>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 bg-navy hover:bg-navy-light text-white text-sm font-bold px-4 py-2 rounded-lg transition">Filtrar</button>
        @if (request()->hasAny(['buscar', 'desde', 'hasta', 'estado']))
            <a href="{{ route('compras.index') }}" class="flex items-center px-3 rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50 text-sm" title="Limpiar filtros">✕</a>
        @endif
    </div>
</form>

<!-- Tabla -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">N°</th>
                    <th class="px-3 py-3 font-bold">Proveedor</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Fecha</th>
                    <th class="px-3 py-3 font-bold hidden lg:table-cell">Registrado por</th>
                    <th class="px-3 py-3 font-bold text-center">Estado</th>
                    <th class="px-3 py-3 font-bold text-right">Total</th>
                    <th class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($compras as $c)
                    <tr class="hover:bg-slate-50 {{ $c->estado === 'anulada' ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('compras.show', $c) }}" class="font-mono text-xs font-bold text-teal-brand hover:text-teal-dark">{{ $c->numero }}</a>
                        </td>
                        <td class="px-3 py-3">
                            <p class="font-semibold text-slate-700">{{ $c->proveedor->razon_social ?? '—' }}</p>
                            @if ($c->proveedor?->ruc)<p class="text-[10px] text-slate-400">RUC {{ $c->proveedor->ruc }}</p>@endif
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $c->fecha->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden lg:table-cell">{{ $c->user->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-[11px] font-bold px-2 py-1 rounded-full
                                {{ $c->estado === 'recibida' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                                {{ ucfirst($c->estado) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right font-bold text-navy {{ $c->estado === 'anulada' ? 'line-through' : '' }}">
                            {{ $moneda }} {{ number_format($c->total, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('compras.show', $c) }}" title="Ver detalle"
                                   class="p-2 rounded-lg text-slate-400 hover:text-teal-brand hover:bg-teal-brand/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </a>
                                @if ($c->estado === 'recibida')
                                    <form method="POST" action="{{ route('compras.anular', $c) }}"
                                          onsubmit="return confirm('¿Anular la compra {{ $c->numero }}? El stock ingresado será revertido.')">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Anular compra"
                                                class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                            <p class="font-semibold">No hay compras registradas</p>
                            <p class="text-xs mt-1"><a href="{{ route('compras.create') }}" class="text-teal-brand font-bold">Registra tu primera compra</a> para ingresar mercadería al stock.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($compras->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $compras->links() }}
        </div>
    @endif
</div>
@endsection
