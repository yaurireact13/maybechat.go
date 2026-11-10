@extends('layouts.app')

@section('titulo', 'Estado de cuenta — ' . $cliente->nombre)

@section('contenido')
@php
    $moneda = auth()->user()->empresa->moneda ?? 'S/';
    $hoy = \Carbon\Carbon::today();
@endphp

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('cuentas.cobrar') }}" class="p-2 rounded-lg hover:bg-slate-200 text-slate-500">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
    </a>
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Estado de cuenta</h1>
        <p class="text-sm text-slate-500">
            {{ $cliente->nombre }}
            @if ($cliente->numero_documento) · {{ $cliente->tipo_documento }} {{ $cliente->numero_documento }} @endif
        </p>
    </div>
</div>

<!-- Resumen -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Crédito total</p>
        <p class="text-xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($totalCredito, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-emerald-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-emerald-500">Pagado</p>
        <p class="text-xl font-extrabold text-emerald-600 mt-1">{{ $moneda }} {{ number_format($totalPagado, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Saldo pendiente</p>
        <p class="text-xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($totalSaldo, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-red-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-red-400">Vencido</p>
        <p class="text-xl font-extrabold text-red-600 mt-1">{{ $moneda }} {{ number_format($totalVencido, 2) }}</p>
    </div>
</div>

<!-- Detalle de ventas a crédito -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 font-bold text-navy">Ventas a crédito</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Venta</th>
                    <th class="px-4 py-3 font-bold">Emitida</th>
                    <th class="px-4 py-3 font-bold">Vence</th>
                    <th class="px-4 py-3 font-bold text-right">Total</th>
                    <th class="px-4 py-3 font-bold text-right">Pagado</th>
                    <th class="px-4 py-3 font-bold text-right">Saldo</th>
                    <th class="px-5 py-3 font-bold text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ventas as $v)
                    @php $vencida = $v->vencida(); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            @if ((float) $v->saldo > 0)
                                <a href="{{ route('cuentas.cobrar.detalle', $v) }}" class="font-bold text-teal-dark hover:underline">{{ $v->numero }}</a>
                            @else
                                <span class="font-bold text-navy">{{ $v->numero }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $v->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 {{ $vencida ? 'text-red-600 font-semibold' : 'text-slate-500' }}">{{ $v->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $moneda }} {{ number_format($v->total, 2) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">{{ $moneda }} {{ number_format($v->pagado(), 2) }}</td>
                        <td class="px-4 py-3 text-right font-extrabold text-navy">{{ $moneda }} {{ number_format($v->saldo, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            @if ((float) $v->saldo <= 0)
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Pagada</span>
                            @elseif ($vencida)
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Vencida</span>
                            @elseif ($v->estadoPago() === 'parcial')
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700">Parcial</span>
                            @else
                                <span class="inline-block text-[11px] font-bold px-2 py-1 rounded-full bg-sky-100 text-sky-700">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-400 py-10">Este cliente no tiene ventas a crédito registradas.</td></tr>
                @endforelse
            </tbody>
            @if ($ventas->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold text-navy">
                        <td class="px-5 py-3" colspan="3">Totales</td>
                        <td class="px-4 py-3 text-right">{{ $moneda }} {{ number_format($totalCredito, 2) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">{{ $moneda }} {{ number_format($totalPagado, 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-600">{{ $moneda }} {{ number_format($totalSaldo, 2) }}</td>
                        <td class="px-5 py-3"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
