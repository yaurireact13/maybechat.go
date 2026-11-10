@extends('layouts.app')

@section('titulo', 'Pago — ' . $compra->numero)

@section('contenido')
@php
    $moneda = auth()->user()->empresa->moneda ?? 'S/';
    $pagado = $compra->pagado();
    $pct = $compra->total > 0 ? min(100, round(($pagado / $compra->total) * 100)) : 0;
    $vencida = $compra->vencida();
@endphp

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('cuentas.pagar') }}" class="p-2 rounded-lg hover:bg-slate-200 text-slate-500">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
    </a>
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Pago de compra {{ $compra->numero }}</h1>
        <p class="text-sm text-slate-500">
            {{ $compra->proveedor->razon_social ?? 'Sin proveedor' }} ·
            emitida {{ $compra->fecha->format('d/m/Y') }} ·
            <a href="{{ route('compras.show', $compra) }}" class="text-teal-dark font-semibold hover:underline">ver compra</a>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">

    <!-- ===== Izquierda ===== -->
    <div class="lg:col-span-2 space-y-5">

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total</p>
                    <p class="text-xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($compra->total, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-500">Pagado</p>
                    <p class="text-xl font-extrabold text-emerald-600 mt-1">{{ $moneda }} {{ number_format($pagado, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-red-400">Saldo</p>
                    <p class="text-xl font-extrabold text-red-600 mt-1">{{ $moneda }} {{ number_format($compra->saldo, 2) }}</p>
                </div>
            </div>
            <div class="mt-4">
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <div class="flex justify-between mt-1.5 text-[11px]">
                    <span class="text-slate-400">{{ $pct }}% pagado</span>
                    <span class="{{ $vencida ? 'text-red-500 font-bold' : 'text-slate-400' }}">
                        Vence: {{ $compra->fecha_vencimiento?->format('d/m/Y') ?? '—' }} {{ $vencida ? '(vencida)' : '' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 font-bold text-navy">Historial de pagos</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                            <th class="px-5 py-2.5 font-bold">Fecha</th>
                            <th class="px-4 py-2.5 font-bold">Método</th>
                            <th class="px-4 py-2.5 font-bold">Referencia</th>
                            <th class="px-4 py-2.5 font-bold">Registró</th>
                            <th class="px-5 py-2.5 font-bold text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($compra->pagos->sortByDesc('fecha') as $pago)
                            <tr>
                                <td class="px-5 py-3 text-slate-600">{{ $pago->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3"><span class="capitalize text-slate-600">{{ $pago->metodo_pago }}</span></td>
                                <td class="px-4 py-3 text-slate-500">{{ $pago->referencia ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $pago->user->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-emerald-600">{{ $moneda }} {{ number_format($pago->monto, 2) }}</td>
                            </tr>
                            @if ($pago->nota)
                                <tr><td colspan="5" class="px-5 pb-3 -mt-2 text-[11px] text-slate-400">Nota: {{ $pago->nota }}</td></tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="text-center text-slate-400 py-8">Aún no se ha registrado ningún pago.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== Derecha ===== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 lg:sticky lg:top-20">
        <h2 class="font-bold text-navy mb-4">Registrar pago</h2>

        @if ((float) $compra->saldo <= 0)
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded-lg px-4 py-3 text-center">
                ✓ Esta compra está totalmente pagada.
            </div>
        @else
            <form method="POST" action="{{ route('cuentas.pagar.pago', $compra) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Monto a pagar ({{ $moneda }})</label>
                    <input type="number" name="monto" step="0.01" min="0.01" max="{{ $compra->saldo }}"
                           value="{{ number_format($compra->saldo, 2, '.', '') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold focus:border-teal-brand outline-none">
                    <button type="button" onclick="this.previousElementSibling.value='{{ number_format($compra->saldo, 2, '.', '') }}'"
                            class="text-[11px] text-teal-dark font-semibold mt-1 hover:underline">Pagar saldo completo</button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Método de pago</label>
                    <select name="metodo_pago" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        <option value="transferencia">Transferencia</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="deposito">Depósito</option>
                        <option value="cheque">Cheque</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Referencia (opcional)</label>
                    <input type="text" name="referencia" maxlength="60" placeholder="N° operación / voucher"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nota (opcional)</label>
                    <input type="text" name="nota" maxlength="255"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
                <button type="submit" class="w-full bg-navy hover:bg-navy-light text-white font-bold py-2.5 rounded-lg shadow-lg shadow-navy/30 transition">
                    Registrar pago
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
