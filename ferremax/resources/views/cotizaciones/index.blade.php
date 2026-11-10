@extends('layouts.app')

@section('titulo', 'Cotizaciones')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Cotizaciones</h1>
        <p class="text-sm text-slate-500">Presupuestos para clientes, convertibles en venta</p>
    </div>
    <a href="{{ route('cotizaciones.create') }}"
       class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva Cotización
    </a>
</div>

<form method="GET" action="{{ route('cotizaciones.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="N° de cotización o cliente"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <select name="estado" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los estados</option>
        <option value="vigente" @selected(request('estado') === 'vigente')>Vigentes</option>
        <option value="aceptada" @selected(request('estado') === 'aceptada')>Aceptadas</option>
        <option value="vencida" @selected(request('estado') === 'vencida')>Vencidas</option>
        <option value="anulada" @selected(request('estado') === 'anulada')>Anuladas</option>
    </select>
    <button type="submit" class="bg-navy hover:bg-navy-light text-white text-sm font-bold px-6 py-2 rounded-lg transition">Buscar</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">N°</th>
                    <th class="px-3 py-3 font-bold">Cliente</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Fecha</th>
                    <th class="px-3 py-3 font-bold hidden sm:table-cell">Vence</th>
                    <th class="px-3 py-3 font-bold text-center">Estado</th>
                    <th class="px-3 py-3 font-bold text-right">Total</th>
                    <th class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cotizaciones as $c)
                    <tr class="hover:bg-slate-50 {{ in_array($c->estado, ['anulada', 'vencida']) ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('cotizaciones.show', $c) }}" class="font-mono text-xs font-bold text-teal-brand hover:text-teal-dark">{{ $c->numero }}</a>
                        </td>
                        <td class="px-3 py-3 font-semibold text-slate-700">{{ $c->cliente->nombre ?? 'Cliente Varios' }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $c->fecha->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden sm:table-cell">{{ $c->vencimiento?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-[11px] font-bold px-2 py-1 rounded-full
                                @if($c->estado === 'vigente') bg-teal-brand/10 text-teal-dark
                                @elseif($c->estado === 'aceptada') bg-emerald-100 text-emerald-700
                                @elseif($c->estado === 'vencida') bg-amber-100 text-amber-700
                                @else bg-red-100 text-red-600 @endif">
                                {{ ucfirst($c->estado) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right font-bold text-navy">{{ $moneda }} {{ number_format($c->total, 2) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('cotizaciones.show', $c) }}" title="Ver / Imprimir"
                                   class="p-2 rounded-lg text-slate-400 hover:text-teal-brand hover:bg-teal-brand/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </a>
                                @if (in_array($c->estado, ['vigente', 'vencida']))
                                    <form method="POST" action="{{ route('cotizaciones.convertir', $c) }}"
                                          onsubmit="return confirm('¿Convertir la cotización {{ $c->numero }} en venta? Se descontará el stock.')">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Convertir en venta"
                                                class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('cotizaciones.anular', $c) }}"
                                          onsubmit="return confirm('¿Anular la cotización {{ $c->numero }}?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Anular"
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
                            <p class="font-semibold">No hay cotizaciones</p>
                            <p class="text-xs mt-1"><a href="{{ route('cotizaciones.create') }}" class="text-teal-brand font-bold">Crea la primera cotización</a> para un cliente.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($cotizaciones->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">{{ $cotizaciones->links() }}</div>
    @endif
</div>
@endsection
