@extends('layouts.app')

@section('titulo', 'Caja')

@section('contenido')
@php
    $moneda = auth()->user()->empresa->moneda ?? 'S/';
    $esperado = $cajaAbierta ? (float) $cajaAbierta->monto_apertura + (float) ($ventasTurno->efectivo ?? 0) : 0;
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-extrabold text-navy">Caja</h1>
    <p class="text-sm text-slate-500">Apertura, cierre y arqueo de efectivo</p>
</div>

@if ($cajaAbierta)
    <!-- Caja abierta -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-emerald-200 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <h2 class="font-bold text-navy">Caja abierta</h2>
                <span class="text-xs text-slate-400 ml-auto">desde {{ $cajaAbierta->fecha_apertura->format('d/m/Y H:i') }} · {{ $cajaAbierta->user->name ?? '' }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-slate-50 rounded-lg p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Monto inicial</p>
                    <p class="text-lg font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($cajaAbierta->monto_apertura, 2) }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Ventas efectivo</p>
                    <p class="text-lg font-extrabold text-emerald-600 mt-1">{{ $moneda }} {{ number_format($ventasTurno->efectivo ?? 0, 2) }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Otros medios</p>
                    <p class="text-lg font-extrabold text-teal-brand mt-1">{{ $moneda }} {{ number_format($ventasTurno->otros ?? 0, 2) }}</p>
                </div>
                <div class="bg-navy rounded-lg p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-300">Efectivo esperado</p>
                    <p class="text-lg font-extrabold text-white mt-1">{{ $moneda }} {{ number_format($esperado, 2) }}</p>
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-3">{{ $ventasTurno->cantidad ?? 0 }} ventas en este turno por {{ $moneda }} {{ number_format($ventasTurno->total ?? 0, 2) }} en total.</p>
        </div>

        <!-- Cierre -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h2 class="font-bold text-navy mb-1">Cerrar caja</h2>
            <p class="text-xs text-slate-500 mb-4">Cuenta el efectivo físico y regístralo.</p>
            <form method="POST" action="{{ route('caja.cerrar', $cajaAbierta) }}" class="space-y-3"
                  onsubmit="return confirm('¿Cerrar la caja con el monto indicado?')">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Efectivo contado ({{ $moneda }}) <span class="text-red-500">*</span></label>
                    <input type="number" name="monto_cierre" id="montoCierre" step="0.01" min="0" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                    <p id="diferencia" class="text-xs mt-1.5"></p>
                </div>
                <button type="submit" class="w-full bg-navy hover:bg-navy-light text-white font-bold py-2.5 rounded-lg transition">Cerrar Caja</button>
            </form>
        </div>
    </div>
@else
    <!-- Apertura -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6 max-w-lg">
        <div class="flex items-center gap-2 mb-1">
            <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
            <h2 class="font-bold text-navy">Caja cerrada</h2>
        </div>
        <p class="text-sm text-slate-500 mb-4">Abre la caja con el efectivo inicial para empezar a vender.</p>
        <form method="POST" action="{{ route('caja.abrir') }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="number" name="monto_apertura" step="0.01" min="0" required placeholder="Monto inicial en {{ $moneda }}"
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            <button type="submit" class="bg-teal-brand hover:bg-teal-dark text-white font-bold px-6 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition">Abrir Caja</button>
        </form>
    </div>
@endif

<!-- Historial -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 pt-5 pb-3"><h2 class="font-bold text-navy text-sm">Historial de cajas</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-y border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Apertura</th>
                    <th class="px-3 py-3 font-bold hidden sm:table-cell">Cierre</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Responsable</th>
                    <th class="px-3 py-3 font-bold text-right">Monto inicial</th>
                    <th class="px-3 py-3 font-bold text-right hidden sm:table-cell">Monto cierre</th>
                    <th class="px-5 py-3 font-bold text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($historial as $c)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $c->fecha_apertura->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden sm:table-cell">{{ $c->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $c->user->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-slate-600">{{ $moneda }} {{ number_format($c->monto_apertura, 2) }}</td>
                        <td class="px-3 py-3 text-right font-semibold text-navy hidden sm:table-cell">{{ $c->monto_cierre !== null ? $moneda . ' ' . number_format($c->monto_cierre, 2) : '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-[11px] font-bold px-2 py-1 rounded-full {{ $c->estado === 'abierta' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ ucfirst($c->estado) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Aún no hay registros de caja.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($historial->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">{{ $historial->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
@if ($cajaAbierta)
<script>
    const ESPERADO = {{ $esperado }};
    const MONEDA = @json($moneda);
    document.getElementById('montoCierre').addEventListener('input', function () {
        const v = parseFloat(this.value);
        const el = document.getElementById('diferencia');
        if (isNaN(v)) { el.textContent = ''; return; }
        const dif = v - ESPERADO;
        if (Math.abs(dif) < 0.005) {
            el.textContent = '✓ Cuadra exacto con lo esperado';
            el.className = 'text-xs mt-1.5 font-bold text-emerald-600';
        } else if (dif > 0) {
            el.textContent = `Sobrante de ${MONEDA} ${dif.toFixed(2)}`;
            el.className = 'text-xs mt-1.5 font-bold text-amber-600';
        } else {
            el.textContent = `Faltante de ${MONEDA} ${Math.abs(dif).toFixed(2)}`;
            el.className = 'text-xs mt-1.5 font-bold text-red-500';
        }
    });
</script>
@endif
@endpush
