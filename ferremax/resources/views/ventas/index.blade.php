@extends('layouts.app')

@section('titulo', 'Ventas')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Historial de Ventas</h1>
        <p class="text-sm text-slate-500">
            {{ $resumen->cantidad }} {{ $resumen->cantidad == 1 ? 'venta completada' : 'ventas completadas' }} por
            <b class="text-navy">{{ $moneda }} {{ number_format($resumen->total, 2) }}</b> según el filtro
        </p>
    </div>
    <a href="{{ route('pos.index') }}"
       class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva Venta
    </a>
</div>

<!-- Filtros -->
<form method="GET" action="{{ route('ventas.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
    <div class="lg:col-span-2 relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="N° de venta, cliente o documento"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <input type="date" name="desde" value="{{ request('desde') }}" title="Desde"
           class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    <input type="date" name="hasta" value="{{ request('hasta') }}" title="Hasta"
           class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    <select name="estado" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los estados</option>
        <option value="completada" @selected(request('estado') === 'completada')>Completadas</option>
        <option value="anulada" @selected(request('estado') === 'anulada')>Anuladas</option>
    </select>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 bg-navy hover:bg-navy-light text-white text-sm font-bold px-4 py-2 rounded-lg transition">Filtrar</button>
        @if (request()->hasAny(['buscar', 'desde', 'hasta', 'metodo_pago', 'estado']))
            <a href="{{ route('ventas.index') }}" class="flex items-center px-3 rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50 text-sm" title="Limpiar filtros">✕</a>
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
                    <th class="px-3 py-3 font-bold">Cliente</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Fecha</th>
                    <th class="px-3 py-3 font-bold hidden lg:table-cell">Vendedor</th>
                    <th class="px-3 py-3 font-bold hidden sm:table-cell">Pago</th>
                    <th class="px-3 py-3 font-bold text-center">Estado</th>
                    <th class="px-3 py-3 font-bold text-center hidden md:table-cell">Comprobante</th>
                    <th class="px-3 py-3 font-bold text-right">Total</th>
                    <th class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ventas as $v)
                    <tr class="hover:bg-slate-50 {{ $v->estado === 'anulada' ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('ventas.show', $v) }}" class="font-mono text-xs font-bold text-teal-brand hover:text-teal-dark">{{ $v->numero }}</a>
                            <p class="text-[10px] text-slate-400 capitalize">{{ $v->tipo_comprobante }}</p>
                        </td>
                        <td class="px-3 py-3 font-semibold text-slate-700">{{ $v->cliente->nombre ?? 'Cliente Varios' }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $v->fecha->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden lg:table-cell">{{ $v->user->name ?? '—' }}</td>
                        <td class="px-3 py-3 hidden sm:table-cell">
                            <span class="text-[11px] font-bold px-2 py-1 rounded-full capitalize
                                {{ $v->metodo_pago === 'efectivo' ? 'bg-lime-brand/20 text-lime-700' : 'bg-teal-brand/10 text-teal-dark' }}">
                                {{ $v->metodo_pago }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-[11px] font-bold px-2 py-1 rounded-full
                                {{ $v->estado === 'completada' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                                {{ ucfirst($v->estado) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center hidden md:table-cell">
                            @if (in_array($v->tipo_comprobante, ['boleta', 'factura'], true))
                                @php
                                    $ce = $v->comprobanteElectronico;
                                    [$cbTxt, $cbCls] = $ce?->estadoBadge() ?? ['En emisión', 'bg-slate-100 text-slate-600'];
                                    $reintentable = $v->estado === 'completada' && (! $ce || $ce->puedeReintentar());
                                @endphp
                                @if ($reintentable)
                                    <form method="POST" action="{{ route('comprobantes.reintentar', $v) }}" class="inline"
                                          onsubmit="return confirm('¿Emitir/reintentar el comprobante electrónico de {{ $v->numero }}?')">
                                        @csrf
                                        <button type="submit" title="Emitir / reintentar comprobante"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-full {{ $cbCls }} hover:ring-2 hover:ring-teal-brand/40 cursor-pointer transition">
                                            {{ $cbTxt }}
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m0 0l-3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] font-bold px-2 py-1 rounded-full {{ $cbCls }}" title="Comprobante {{ $cbTxt }}">{{ $cbTxt }}</span>
                                @endif
                            @else
                                <span class="text-[11px] text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right font-bold text-navy {{ $v->estado === 'anulada' ? 'line-through' : '' }}">
                            {{ $moneda }} {{ number_format($v->total, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('ventas.show', $v) }}" title="Ver detalle"
                                   class="p-2 rounded-lg text-slate-400 hover:text-teal-brand hover:bg-teal-brand/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </a>
                                <a href="{{ route('pos.recibo', $v) }}" target="_blank" title="Imprimir ticket"
                                   class="p-2 rounded-lg text-slate-400 hover:text-navy hover:bg-slate-100 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" /></svg>
                                </a>
                                @if ($v->estado === 'completada')
                                    <form method="POST" action="{{ route('ventas.anular', $v) }}"
                                          onsubmit="return confirm('¿Anular la venta {{ $v->numero }}? El stock será devuelto al inventario.')">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Anular venta"
                                                class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-slate-400">No se encontraron ventas con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($ventas->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $ventas->links() }}
        </div>
    @endif
</div>
@endsection
