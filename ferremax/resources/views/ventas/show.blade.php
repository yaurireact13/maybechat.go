@extends('layouts.app')

@section('titulo', 'Venta ' . $venta->numero)

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="max-w-3xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-navy transition" title="Volver">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-navy">{{ $venta->numero }}</h1>
                    <span class="text-[11px] font-bold px-2 py-1 rounded-full
                        {{ $venta->estado === 'completada' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                        {{ ucfirst($venta->estado) }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 capitalize">{{ $venta->tipo_comprobante }} — {{ $venta->fecha->locale('es')->isoFormat('dddd D [de] MMMM YYYY, HH:mm') }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pos.recibo', $venta) }}" target="_blank"
               class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-bold px-4 py-2.5 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" /></svg>
                Ticket
            </a>
            @if ($venta->estado === 'completada')
                <form method="POST" action="{{ route('ventas.anular', $venta) }}"
                      onsubmit="return confirm('¿Anular la venta {{ $venta->numero }}? El stock será devuelto al inventario.')">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-red-50 border border-red-200 hover:bg-red-100 text-red-600 text-sm font-bold px-4 py-2.5 rounded-lg transition">
                        Anular Venta
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Información general -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Cliente</p>
            <p class="font-semibold text-slate-700">{{ $venta->cliente->nombre ?? 'Cliente Varios' }}</p>
            @if ($venta->cliente?->numero_documento)
                <p class="text-xs text-slate-400">{{ $venta->cliente->tipo_documento }}: {{ $venta->cliente->numero_documento }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Vendedor</p>
            <p class="font-semibold text-slate-700">{{ $venta->user->name ?? '—' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Método de pago</p>
            <p class="font-semibold text-slate-700 capitalize">{{ $venta->metodo_pago }}</p>
        </div>
    </div>

    <!-- Comprobante electrónico -->
    @if (in_array($venta->tipo_comprobante, ['boleta', 'factura'], true))
        @php $ce = $venta->comprobanteElectronico; [$badgeTxt, $badgeCls] = $ce ? $ce->estadoBadge() : ['En emisión', 'bg-slate-100 text-slate-600']; @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-lg bg-teal-brand/10 text-teal-dark flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                    </span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Comprobante electrónico</p>
                        <p class="font-semibold text-slate-700">
                            {{ $ce && $ce->serie ? $ce->serie . '-' . $ce->numero : 'Pendiente de emisión' }}
                            @if ($ce && $ce->modo === 'simulado') <span class="text-[10px] text-amber-600 font-bold">(SIMULADO)</span> @endif
                        </p>
                        @if ($ce?->codigo_unico)
                            <p class="text-[11px] text-slate-400">Código: {{ $ce->codigo_unico }}</p>
                        @elseif ($ce?->mensaje)
                            <p class="text-[11px] text-slate-400">{{ $ce->mensaje }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $badgeCls }}">{{ $badgeTxt }}</span>
                    @if ($venta->estado === 'completada' && (! $ce || $ce->puedeReintentar()))
                        <form method="POST" action="{{ route('comprobantes.reintentar', $venta) }}">
                            @csrf
                            <button class="text-xs font-bold px-3 py-1.5 rounded-lg bg-teal-brand hover:bg-teal-dark text-white transition">
                                {{ $ce ? 'Reintentar' : 'Emitir' }}
                            </button>
                        </form>
                    @endif
                    @if ($ce?->pdf_url)
                        <a href="{{ $ce->pdf_url }}" target="_blank" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">PDF</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Detalle -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                        <th class="px-5 py-3 font-bold">Producto</th>
                        <th class="px-3 py-3 font-bold text-center">Cantidad</th>
                        <th class="px-3 py-3 font-bold text-right hidden sm:table-cell">P. Unitario</th>
                        <th class="px-5 py-3 font-bold text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($venta->detalles as $d)
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
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ $moneda }} {{ number_format($venta->subtotal, 2) }}</span></div>
                <div class="flex justify-between text-slate-500"><span>IGV</span><span>{{ $moneda }} {{ number_format($venta->impuesto, 2) }}</span></div>
                @if ($venta->descuento > 0)
                    <div class="flex justify-between text-red-500"><span>Descuento</span><span>− {{ $moneda }} {{ number_format($venta->descuento, 2) }}</span></div>
                @endif
                <div class="flex justify-between font-extrabold text-navy text-lg pt-1 border-t border-slate-200"><span>TOTAL</span><span>{{ $moneda }} {{ number_format($venta->total, 2) }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
