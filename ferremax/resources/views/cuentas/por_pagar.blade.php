@extends('layouts.app')

@section('titulo', 'Cuentas por Pagar')

@section('contenido')
@php
    $moneda = auth()->user()->empresa->moneda ?? 'S/';
    $hoy = \Carbon\Carbon::today();
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-extrabold text-navy">Cuentas por Pagar</h1>
    <p class="text-sm text-slate-500">Compras a crédito con saldo pendiente a tus proveedores</p>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total por pagar</p>
        <p class="text-2xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($totalPorPagar, 2) }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">{{ $numDocs }} {{ $numDocs == 1 ? 'documento' : 'documentos' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-red-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-red-400">Vencido</p>
        <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $moneda }} {{ number_format($totalVencido, 2) }}</p>
        <p class="text-[11px] text-red-400 mt-0.5">{{ $numVencidas }} {{ $numVencidas == 1 ? 'documento vencido' : 'documentos vencidos' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-emerald-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-emerald-500">Por vencer / al día</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $moneda }} {{ number_format($totalPorPagar - $totalVencido, 2) }}</p>
        <p class="text-[11px] text-emerald-500 mt-0.5">{{ $numDocs - $numVencidas }} documentos vigentes</p>
    </div>
    <div class="bg-gradient-to-br from-navy to-navy-dark rounded-xl p-4 text-white">
        <p class="text-xs font-bold uppercase tracking-wide text-white/70">Deuda en mora</p>
        @php $mora = $totalPorPagar > 0 ? round(($totalVencido / $totalPorPagar) * 100) : 0; @endphp
        <p class="text-2xl font-extrabold mt-1">{{ $mora }}%</p>
        <p class="text-[11px] text-white/70 mt-0.5">de la deuda total</p>
    </div>
</div>

<!-- Filtros -->
<form method="GET" action="{{ route('cuentas.pagar') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="N° de compra o proveedor"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <select name="proveedor_id" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los proveedores</option>
        @foreach ($proveedores as $p)
            <option value="{{ $p->id }}" @selected(request('proveedor_id') == $p->id)>{{ $p->razon_social }}</option>
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
        <a href="{{ route('cuentas.pagar') }}" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-navy">Limpiar</a>
    </div>
</form>

<!-- Tabla -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-4 py-3 font-bold">Compra</th>
                    <th class="px-4 py-3 font-bold">Proveedor</th>
                    <th class="px-4 py-3 font-bold">Vencimiento</th>
                    <th class="px-4 py-3 font-bold text-right">Total</th>
                    <th class="px-4 py-3 font-bold text-right">Pagado</th>
                    <th class="px-4 py-3 font-bold text-right">Saldo</th>
                    <th class="px-4 py-3 font-bold text-center">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($compras as $c)
                    @php
                        $vencida = $c->vencida();
                        $dias = $c->fecha_vencimiento ? $hoy->diffInDays($c->fecha_vencimiento, false) : null;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="font-bold text-navy">{{ $c->numero }}</span>
                            <span class="block text-[11px] text-slate-400">{{ $c->fecha->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $c->proveedor->razon_social ?? 'Sin proveedor' }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $vencida ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                {{ $c->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                            </span>
                            @if (!is_null($dias))
                                <span class="block text-[11px] {{ $vencida ? 'text-red-400' : 'text-slate-400' }}">
                                    {{ $vencida ? 'venció hace ' . abs($dias) . ' d' : ($dias == 0 ? 'vence hoy' : 'en ' . $dias . ' d') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $moneda }} {{ number_format($c->total, 2) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">{{ $moneda }} {{ number_format($c->pagado(), 2) }}</td>
                        <td class="px-4 py-3 text-right font-extrabold text-navy">{{ $moneda }} {{ number_format($c->saldo, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($vencida)
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Vencida</span>
                            @elseif ($c->estadoPago() === 'parcial')
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700">Parcial</span>
                            @else
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-sky-100 text-sky-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cuentas.pagar.detalle', $c) }}"
                               class="inline-flex items-center gap-1 bg-navy hover:bg-navy-light text-white text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                Pagar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-12">No hay cuentas por pagar con los filtros aplicados. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $compras->links() }}</div>
@endsection
