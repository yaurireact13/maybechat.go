@extends('layouts.app')

@section('titulo', 'Cuentas por Cobrar')

@section('contenido')
@php
    $moneda = auth()->user()->empresa->moneda ?? 'S/';
    $hoy = \Carbon\Carbon::today();
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-extrabold text-navy">Cuentas por Cobrar</h1>
    <p class="text-sm text-slate-500">Ventas a crédito con saldo pendiente de tus clientes</p>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total por cobrar</p>
        <p class="text-2xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($totalPorCobrar, 2) }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">{{ $numDocs }} {{ $numDocs == 1 ? 'documento' : 'documentos' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-red-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-red-400">Vencido</p>
        <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $moneda }} {{ number_format($totalVencido, 2) }}</p>
        <p class="text-[11px] text-red-400 mt-0.5">{{ $numVencidas }} {{ $numVencidas == 1 ? 'documento vencido' : 'documentos vencidos' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-emerald-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-emerald-500">Por vencer / al día</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $moneda }} {{ number_format($totalPorCobrar - $totalVencido, 2) }}</p>
        <p class="text-[11px] text-emerald-500 mt-0.5">{{ $numDocs - $numVencidas }} documentos vigentes</p>
    </div>
    <div class="bg-gradient-to-br from-teal-brand to-teal-dark rounded-xl p-4 text-white">
        <p class="text-xs font-bold uppercase tracking-wide text-white/70">% cobrado / cartera</p>
        @php $cartera = $totalPorCobrar > 0 ? round(($totalVencido / $totalPorCobrar) * 100) : 0; @endphp
        <p class="text-2xl font-extrabold mt-1">{{ 100 - $cartera }}% vigente</p>
        <p class="text-[11px] text-white/70 mt-0.5">{{ $cartera }}% en mora</p>
    </div>
</div>

<!-- Filtros -->
<form method="GET" action="{{ route('cuentas.cobrar') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="N° de venta o cliente"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <select name="cliente_id" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los clientes</option>
        @foreach ($clientes as $c)
            <option value="{{ $c->id }}" @selected(request('cliente_id') == $c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    <select name="estado" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los estados</option>
        <option value="vencidas" @selected(request('estado') == 'vencidas')>Vencidas</option>
        <option value="porvencer" @selected(request('estado') == 'porvencer')>Por vencer / al día</option>
        <option value="parciales" @selected(request('estado') == 'parciales')>Con abono parcial</option>
    </select>
    <div class="flex gap-2">
        <button class="flex-1 bg-navy hover:bg-navy-light text-white text-sm font-bold rounded-lg px-4 py-2 transition">Filtrar</button>
        <a href="{{ route('cuentas.cobrar') }}" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-navy">Limpiar</a>
    </div>
</form>

<!-- Tabla -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-4 py-3 font-bold">Venta</th>
                    <th class="px-4 py-3 font-bold">Cliente</th>
                    <th class="px-4 py-3 font-bold">Vencimiento</th>
                    <th class="px-4 py-3 font-bold text-right">Total</th>
                    <th class="px-4 py-3 font-bold text-right">Pagado</th>
                    <th class="px-4 py-3 font-bold text-right">Saldo</th>
                    <th class="px-4 py-3 font-bold text-center">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ventas as $v)
                    @php
                        $vencida = $v->vencida();
                        $dias = $v->fecha_vencimiento ? $hoy->diffInDays($v->fecha_vencimiento, false) : null;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="font-bold text-navy">{{ $v->numero }}</span>
                            <span class="block text-[11px] text-slate-400">{{ $v->fecha->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $v->cliente->nombre ?? 'Sin cliente' }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $vencida ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                {{ $v->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                            </span>
                            @if (!is_null($dias))
                                <span class="block text-[11px] {{ $vencida ? 'text-red-400' : 'text-slate-400' }}">
                                    {{ $vencida ? 'venció hace ' . abs($dias) . ' d' : ($dias == 0 ? 'vence hoy' : 'en ' . $dias . ' d') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $moneda }} {{ number_format($v->total, 2) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">{{ $moneda }} {{ number_format($v->pagado(), 2) }}</td>
                        <td class="px-4 py-3 text-right font-extrabold text-navy">{{ $moneda }} {{ number_format($v->saldo, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($vencida)
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Vencida</span>
                            @elseif ($v->estadoPago() === 'parcial')
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700">Parcial</span>
                            @else
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-sky-100 text-sky-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cuentas.cobrar.detalle', $v) }}"
                               class="inline-flex items-center gap-1 bg-teal-brand hover:bg-teal-dark text-white text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                Cobrar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-12">No hay cuentas por cobrar con los filtros aplicados. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $ventas->links() }}</div>
@endsection
