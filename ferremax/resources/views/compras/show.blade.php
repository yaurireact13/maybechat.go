@extends('layouts.app')

@section('titulo', 'Compra ' . $compra->numero)

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="max-w-3xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('compras.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-navy transition" title="Volver">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-navy">{{ $compra->numero }}</h1>
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full
                        {{ $compra->estado === 'recibida' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                        {{ ucfirst($compra->estado) }}
                    </span>
                </div>
                <p class="text-sm text-slate-500">{{ $compra->fecha->locale('es')->isoFormat('dddd D [de] MMMM YYYY') }}</p>
            </div>
        </div>
        @if ($compra->estado === 'recibida')
            <form method="POST" action="{{ route('compras.anular', $compra) }}"
                  onsubmit="return confirm('¿Anular la compra {{ $compra->numero }}? El stock ingresado será revertido.')">
                @csrf @method('PATCH')
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-red-50 border border-red-200 hover:bg-red-100 text-red-600 text-sm font-bold px-4 py-2.5 rounded-lg transition">
                    Anular Compra
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:col-span-2">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Proveedor</p>
            <p class="font-semibold text-slate-700">{{ $compra->proveedor->razon_social ?? '—' }}</p>
            @if ($compra->proveedor?->ruc)<p class="text-xs text-slate-400">RUC: {{ $compra->proveedor->ruc }}</p>@endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Registrado por</p>
            <p class="font-semibold text-slate-700">{{ $compra->user->name ?? '—' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                        <th class="px-5 py-3 font-bold">Producto</th>
                        <th class="px-3 py-3 font-bold text-center">Cantidad</th>
                        <th class="px-3 py-3 font-bold text-right hidden sm:table-cell">Costo unit.</th>
                        <th class="px-5 py-3 font-bold text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($compra->detalles as $d)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-700">{{ $d->producto->nombre ?? 'Producto eliminado' }}</p>
                                <p class="text-[11px] text-slate-400 font-mono">{{ $d->producto->codigo ?? '' }}</p>
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-slate-600">{{ $d->cantidad + 0 }} <span class="text-[10px] text-slate-400 font-semibold">{{ $d->producto->unidad->abreviatura ?? '' }}</span></td>
                            <td class="px-3 py-3 text-right text-slate-500 hidden sm:table-cell">{{ $moneda }} {{ number_format($d->precio, 2) }}</td>
                            <td class="px-5 py-3 text-right font-bold text-navy">{{ $moneda }} {{ number_format($d->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">
            <div class="ml-auto max-w-xs space-y-1 text-sm">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ $moneda }} {{ number_format($compra->subtotal, 2) }}</span></div>
                <div class="flex justify-between text-slate-500"><span>IGV</span><span>{{ $moneda }} {{ number_format($compra->impuesto, 2) }}</span></div>
                <div class="flex justify-between font-extrabold text-navy text-lg pt-1 border-t border-slate-200"><span>TOTAL</span><span>{{ $moneda }} {{ number_format($compra->total, 2) }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
