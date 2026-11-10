@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

@include('partials.limite_plan', ['recurso' => 'productos'])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Productos</h1>
        <p class="text-sm text-slate-500">{{ $productos->total() }} {{ $productos->total() == 1 ? 'producto' : 'productos' }} en el catálogo</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('catalogos.index') }}"
           class="inline-flex items-center justify-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-bold px-4 py-2.5 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
            Catálogos
        </a>
        <a href="{{ route('productos.create') }}"
           class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Nuevo Producto
        </a>
    </div>
</div>

<!-- Filtros -->
<form method="GET" action="{{ route('productos.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
    <div class="lg:col-span-2 relative">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, código o código de barras"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <select name="categoria" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="">Todas las categorías</option>
        @foreach ($categorias as $c)
            <option value="{{ $c->id }}" @selected(request('categoria') == $c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    <select name="estado" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="activos" @selected(request('estado', 'activos') === 'activos')>Activos</option>
        <option value="stock_bajo" @selected(request('estado') === 'stock_bajo')>Stock bajo</option>
        <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
    </select>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 bg-navy hover:bg-navy-light text-white text-sm font-bold px-4 py-2 rounded-lg transition">Filtrar</button>
        @if (request()->hasAny(['buscar', 'categoria', 'marca', 'estado']))
            <a href="{{ route('productos.index') }}" class="flex items-center px-3 rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50 text-sm" title="Limpiar filtros">✕</a>
        @endif
    </div>
</form>

<!-- Tabla -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Producto</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Categoría</th>
                    <th class="px-3 py-3 font-bold hidden lg:table-cell">Marca</th>
                    <th class="px-3 py-3 font-bold text-right hidden sm:table-cell">P. Compra</th>
                    <th class="px-3 py-3 font-bold text-right">P. Venta</th>
                    <th class="px-3 py-3 font-bold text-center">Stock</th>
                    <th class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($productos as $p)
                    <tr class="hover:bg-slate-50 {{ !$p->activo ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-slate-700">{{ $p->nombre }}</p>
                            <p class="text-[11px] text-slate-400 font-mono">{{ $p->codigo }}@if($p->unidad) · {{ $p->unidad->abreviatura }}@endif</p>
                        </td>
                        <td class="px-3 py-3 text-slate-500 hidden md:table-cell">{{ $p->categoria->nombre ?? '—' }}</td>
                        <td class="px-3 py-3 text-slate-500 hidden lg:table-cell">{{ $p->marca->nombre ?? '—' }}</td>
                        <td class="px-3 py-3 text-right text-slate-500 hidden sm:table-cell">{{ $moneda }} {{ number_format($p->precio_compra, 2) }}</td>
                        <td class="px-3 py-3 text-right font-bold text-navy">{{ $moneda }} {{ number_format($p->precio_venta, 2) }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex items-center justify-center min-w-[3rem] text-xs font-bold px-2 py-1 rounded-full
                                {{ $p->stock <= $p->stock_minimo ? 'bg-red-100 text-red-600' : 'bg-lime-brand/20 text-lime-700' }}">
                                {{ $p->stock + 0 }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('productos.edit', $p) }}" title="Editar"
                                   class="p-2 rounded-lg text-slate-400 hover:text-teal-brand hover:bg-teal-brand/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                </a>
                                @if ($p->activo)
                                    <form method="POST" action="{{ route('productos.destroy', $p) }}"
                                          onsubmit="return confirm('¿Desactivar el producto «{{ $p->nombre }}»?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Desactivar"
                                                class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('productos.activar', $p) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Reactivar"
                                                class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                            <p class="font-semibold">No se encontraron productos</p>
                            <p class="text-xs mt-1">Prueba con otros filtros o <a href="{{ route('productos.create') }}" class="text-teal-brand font-bold">crea un producto nuevo</a>.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($productos->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $productos->links() }}
        </div>
    @endif
</div>
@endsection
