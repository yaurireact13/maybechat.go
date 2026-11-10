@extends('layouts.app')

@section('titulo', 'Cotización ' . $cotizacion->numero)

@section('contenido')
@php $moneda = $empresa->moneda ?? 'S/'; @endphp

<div class="max-w-3xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 no-print">
        <div class="flex items-center gap-3">
            <a href="{{ route('cotizaciones.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-navy transition" title="Volver">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-navy">{{ $cotizacion->numero }}</h1>
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full
                        @if($cotizacion->estado === 'vigente') bg-teal-brand/10 text-teal-dark
                        @elseif($cotizacion->estado === 'aceptada') bg-emerald-100 text-emerald-700
                        @elseif($cotizacion->estado === 'vencida') bg-amber-100 text-amber-700
                        @else bg-red-100 text-red-600 @endif">
                        {{ ucfirst($cotizacion->estado) }}
                    </span>
                </div>
                <p class="text-sm text-slate-500">Válida hasta {{ $cotizacion->vencimiento?->format('d/m/Y') ?? 'sin vencimiento' }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-bold px-4 py-2.5 rounded-lg transition">
                🖨 Imprimir
            </button>
            @if (in_array($cotizacion->estado, ['vigente', 'vencida']))
                <form method="POST" action="{{ route('cotizaciones.convertir', $cotizacion) }}"
                      onsubmit="return confirm('¿Convertir en venta? Se descontará el stock.')">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                        Convertir en Venta
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Documento -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8" id="docCotizacion">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4 pb-5 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-extrabold text-navy">{{ $empresa->nombre }}</h2>
                @if ($empresa->ruc)<p class="text-xs text-slate-500">RUC: {{ $empresa->ruc }}</p>@endif
                @if ($empresa->direccion)<p class="text-xs text-slate-500">{{ $empresa->direccion }}</p>@endif
                @if ($empresa->telefono)<p class="text-xs text-slate-500">Tel: {{ $empresa->telefono }}</p>@endif
            </div>
            <div class="sm:text-right">
                <p class="text-sm font-bold text-teal-brand uppercase tracking-wide">Cotización</p>
                <p class="text-lg font-extrabold text-navy">{{ $cotizacion->numero }}</p>
                <p class="text-xs text-slate-500">Fecha: {{ $cotizacion->fecha->format('d/m/Y') }}</p>
                <p class="text-xs text-slate-500">Vence: {{ $cotizacion->vencimiento?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>

        <div class="py-4 text-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Cliente</p>
            <p class="font-semibold text-slate-700">{{ $cotizacion->cliente->nombre ?? 'Cliente Varios' }}</p>
            @if ($cotizacion->cliente?->numero_documento)
                <p class="text-xs text-slate-500">{{ $cotizacion->cliente->tipo_documento }}: {{ $cotizacion->cliente->numero_documento }}</p>
            @endif
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-y border-slate-200">
                    <th class="py-2.5 font-bold">Producto</th>
                    <th class="py-2.5 font-bold text-center">Cant.</th>
                    <th class="py-2.5 font-bold text-right">P. Unit.</th>
                    <th class="py-2.5 font-bold text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($cotizacion->detalles as $d)
                    <tr>
                        <td class="py-2.5">
                            <p class="font-semibold text-slate-700">{{ $d->producto->nombre ?? '—' }}</p>
                            <p class="text-[10px] font-mono text-slate-400">{{ $d->producto->codigo ?? '' }}</p>
                        </td>
                        <td class="py-2.5 text-center">{{ $d->cantidad + 0 }} {{ $d->producto->unidad->abreviatura ?? '' }}</td>
                        <td class="py-2.5 text-right text-slate-500">{{ $moneda }} {{ number_format($d->precio, 2) }}</td>
                        <td class="py-2.5 text-right font-bold text-navy">{{ $moneda }} {{ number_format($d->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $imp = (float) ($empresa->impuesto ?? 18);
            $sub = round($cotizacion->total / (1 + $imp / 100), 2);
        @endphp
        <div class="flex justify-end pt-4 border-t border-slate-200 mt-2">
            <div class="w-full max-w-xs space-y-1 text-sm">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ $moneda }} {{ number_format($sub, 2) }}</span></div>
                <div class="flex justify-between text-slate-500"><span>IGV ({{ $imp + 0 }}%)</span><span>{{ $moneda }} {{ number_format($cotizacion->total - $sub, 2) }}</span></div>
                <div class="flex justify-between font-extrabold text-navy text-lg pt-1 border-t border-slate-200"><span>TOTAL</span><span>{{ $moneda }} {{ number_format($cotizacion->total, 2) }}</span></div>
            </div>
        </div>

        <p class="text-[11px] text-slate-400 mt-6">Precios incluyen IGV. Cotización válida hasta la fecha indicada o agotar stock. Elaborada por {{ $cotizacion->user->name ?? '' }}.</p>
    </div>
</div>

<style>
    @media print {
        aside, header, footer, .no-print { display: none !important; }
        main { padding: 0 !important; }
        body { background: #fff !important; }
        #docCotizacion { border: none !important; box-shadow: none !important; }
        .lg\:ml-64 { margin-left: 0 !important; }
    }
</style>
@endsection
