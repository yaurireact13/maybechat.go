@extends('layouts.app')

@section('titulo', 'Inventario / Kardex')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Inventario / Kardex</h1>
        <p class="text-sm text-slate-500">Movimientos de stock y ajustes manuales</p>
    </div>
    <button onclick="document.getElementById('modalAjuste').classList.remove('hidden')"
            class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" /></svg>
        Ajustar Stock
    </button>
</div>

<!-- Resumen -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Inventario valorizado</p>
        <p class="text-xl sm:text-2xl font-extrabold text-navy mt-1">{{ $moneda }} {{ number_format($resumen['valorizado'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Unidades en stock</p>
        <p class="text-xl sm:text-2xl font-extrabold text-navy mt-1">{{ number_format($resumen['unidades']) }}</p>
    </div>
    <div class="bg-white rounded-xl border {{ $resumen['stock_bajo'] > 0 ? 'border-amber-200' : 'border-slate-200' }} p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Stock bajo</p>
        <p class="text-xl sm:text-2xl font-extrabold {{ $resumen['stock_bajo'] > 0 ? 'text-amber-500' : 'text-navy' }} mt-1">{{ $resumen['stock_bajo'] }}</p>
    </div>
    <div class="bg-white rounded-xl border {{ $resumen['sin_stock'] > 0 ? 'border-red-200' : 'border-slate-200' }} p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Sin stock</p>
        <p class="text-xl sm:text-2xl font-extrabold {{ $resumen['sin_stock'] > 0 ? 'text-red-500' : 'text-navy' }} mt-1">{{ $resumen['sin_stock'] }}</p>
    </div>
</div>

<!-- Filtros -->
<form method="GET" action="{{ route('inventario.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
    <select name="producto_id" class="lg:col-span-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los productos</option>
        @foreach ($productos as $p)
            <option value="{{ $p->id }}" @selected(request('producto_id') == $p->id)>{{ $p->nombre }} ({{ $p->codigo }})</option>
        @endforeach
    </select>
    <select name="tipo" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todos los tipos</option>
        <option value="entrada" @selected(request('tipo') === 'entrada')>Entradas</option>
        <option value="salida" @selected(request('tipo') === 'salida')>Salidas</option>
        <option value="ajuste" @selected(request('tipo') === 'ajuste')>Ajustes</option>
    </select>
    <input type="date" name="desde" value="{{ request('desde') }}"
           class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    <input type="date" name="hasta" value="{{ request('hasta') }}"
           class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    <div class="flex gap-2">
        <button type="submit" class="flex-1 bg-navy hover:bg-navy-light text-white text-sm font-bold px-4 py-2 rounded-lg transition">Filtrar</button>
        @if (request()->hasAny(['producto_id', 'tipo', 'desde', 'hasta']))
            <a href="{{ route('inventario.index') }}" class="flex items-center px-3 rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50 text-sm">✕</a>
        @endif
    </div>
</form>

<!-- Kardex -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Fecha</th>
                    <th class="px-3 py-3 font-bold">Producto</th>
                    <th class="px-3 py-3 font-bold text-center">Tipo</th>
                    <th class="px-3 py-3 font-bold text-center">Cantidad</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Motivo</th>
                    <th class="px-5 py-3 font-bold hidden lg:table-cell">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($movimientos as $m)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $m->fecha->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3">
                            <p class="font-semibold text-slate-700">{{ $m->producto->nombre ?? '—' }}</p>
                            <p class="text-[10px] font-mono text-slate-400">{{ $m->producto->codigo ?? '' }}</p>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-[11px] font-bold px-2 py-1 rounded-full capitalize
                                @if($m->tipo === 'entrada') bg-emerald-100 text-emerald-700
                                @elseif($m->tipo === 'salida') bg-red-100 text-red-600
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ $m->tipo }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center font-bold {{ $m->tipo === 'salida' ? 'text-red-500' : 'text-emerald-600' }}">
                            {{ $m->tipo === 'salida' ? '−' : '+' }}{{ $m->cantidad + 0 }}
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $m->motivo }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500 hidden lg:table-cell">{{ $m->user->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No hay movimientos con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($movimientos->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">{{ $movimientos->links() }}</div>
    @endif
</div>

<!-- Modal de ajuste -->
<div id="modalAjuste" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modalAjuste').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h2 class="text-lg font-extrabold text-navy mb-1">Ajustar Stock</h2>
        <p class="text-xs text-slate-500 mb-4">Para mermas, roturas, conteos físicos o correcciones.</p>
        <form method="POST" action="{{ route('inventario.ajustar') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Producto <span class="text-red-500">*</span></label>
                <select name="producto_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                    <option value="">— Selecciona —</option>
                    @foreach ($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }} (stock: {{ $p->stock + 0 }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tipo <span class="text-red-500">*</span></label>
                    <select name="tipo" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        <option value="entrada">Entrada (+)</option>
                        <option value="salida">Salida (−)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Cantidad <span class="text-red-500">*</span></label>
                    <input type="number" name="cantidad" step="0.01" min="0.01" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Motivo <span class="text-red-500">*</span></label>
                <input type="text" name="motivo" required placeholder="Merma, conteo físico, rotura..."
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalAjuste').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-slate-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg transition">Registrar Ajuste</button>
            </div>
        </form>
    </div>
</div>
@endsection
