@extends('layouts.app')

@section('titulo', 'Catálogos')

@section('contenido')
<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('productos.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-navy transition" title="Volver a productos">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
    </a>
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Catálogos</h1>
        <p class="text-sm text-slate-500">Categorías, marcas y unidades de medida para tus productos</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- ===== Categorías ===== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-5 pb-3 border-b border-slate-100">
            <h2 class="font-bold text-navy">Categorías <span class="text-xs font-semibold text-slate-400">({{ $categorias->count() }})</span></h2>
        </div>
        <form method="POST" action="{{ route('catalogos.categorias.store') }}" class="p-4 flex gap-2 border-b border-slate-100 bg-slate-50/50">
            @csrf
            <input type="text" name="nombre" required placeholder="Nueva categoría..."
                   class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            <button type="submit" class="shrink-0 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2 rounded-lg transition">+</button>
        </form>
        <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
            @forelse ($categorias as $c)
                <div class="px-5 py-3 flex items-center gap-2 group">
                    <form method="POST" action="{{ route('catalogos.categorias.update', $c) }}" class="flex-1 flex items-center gap-2 min-w-0">
                        @csrf @method('PUT')
                        <input type="text" name="nombre" value="{{ $c->nombre }}" required
                               class="flex-1 min-w-0 bg-transparent text-sm font-semibold text-slate-700 rounded px-1 py-0.5 border border-transparent focus:border-teal-brand focus:bg-white outline-none transition">
                        <span class="text-[11px] text-slate-400 shrink-0">{{ $c->productos_count }} prod.</span>
                        <button type="submit" title="Guardar cambio" class="shrink-0 p-1.5 rounded text-slate-300 hover:text-teal-brand opacity-0 group-hover:opacity-100 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('catalogos.categorias.destroy', $c) }}"
                          onsubmit="return confirm('¿Eliminar la categoría «{{ $c->nombre }}»?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Eliminar" class="p-1.5 rounded text-slate-300 hover:text-red-500 transition" @disabled($c->productos_count > 0)>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400 text-center">Sin categorías aún.</p>
            @endforelse
        </div>
    </div>

    <!-- ===== Marcas ===== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-5 pb-3 border-b border-slate-100">
            <h2 class="font-bold text-navy">Marcas <span class="text-xs font-semibold text-slate-400">({{ $marcas->count() }})</span></h2>
        </div>
        <form method="POST" action="{{ route('catalogos.marcas.store') }}" class="p-4 flex gap-2 border-b border-slate-100 bg-slate-50/50">
            @csrf
            <input type="text" name="nombre" required placeholder="Nueva marca..."
                   class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            <button type="submit" class="shrink-0 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2 rounded-lg transition">+</button>
        </form>
        <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
            @forelse ($marcas as $m)
                <div class="px-5 py-3 flex items-center gap-2 group">
                    <form method="POST" action="{{ route('catalogos.marcas.update', $m) }}" class="flex-1 flex items-center gap-2 min-w-0">
                        @csrf @method('PUT')
                        <input type="text" name="nombre" value="{{ $m->nombre }}" required
                               class="flex-1 min-w-0 bg-transparent text-sm font-semibold text-slate-700 rounded px-1 py-0.5 border border-transparent focus:border-teal-brand focus:bg-white outline-none transition">
                        <span class="text-[11px] text-slate-400 shrink-0">{{ $m->productos_count }} prod.</span>
                        <button type="submit" title="Guardar cambio" class="shrink-0 p-1.5 rounded text-slate-300 hover:text-teal-brand opacity-0 group-hover:opacity-100 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('catalogos.marcas.destroy', $m) }}"
                          onsubmit="return confirm('¿Eliminar la marca «{{ $m->nombre }}»?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Eliminar" class="p-1.5 rounded text-slate-300 hover:text-red-500 transition" @disabled($m->productos_count > 0)>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400 text-center">Sin marcas aún.</p>
            @endforelse
        </div>
    </div>

    <!-- ===== Unidades ===== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-5 pb-3 border-b border-slate-100">
            <h2 class="font-bold text-navy">Unidades de medida <span class="text-xs font-semibold text-slate-400">({{ $unidades->count() }})</span></h2>
        </div>
        <form method="POST" action="{{ route('catalogos.unidades.store') }}" class="p-4 flex gap-2 border-b border-slate-100 bg-slate-50/50">
            @csrf
            <input type="text" name="nombre" required placeholder="Nombre (Metro)"
                   class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            <input type="text" name="abreviatura" required placeholder="MT" maxlength="10"
                   class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            <button type="submit" class="shrink-0 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2 rounded-lg transition">+</button>
        </form>
        <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
            @forelse ($unidades as $u)
                <div class="px-5 py-3 flex items-center gap-2 group">
                    <form method="POST" action="{{ route('catalogos.unidades.update', $u) }}" class="flex-1 flex items-center gap-2 min-w-0">
                        @csrf @method('PUT')
                        <input type="text" name="nombre" value="{{ $u->nombre }}" required
                               class="flex-1 min-w-0 bg-transparent text-sm font-semibold text-slate-700 rounded px-1 py-0.5 border border-transparent focus:border-teal-brand focus:bg-white outline-none transition">
                        <input type="text" name="abreviatura" value="{{ $u->abreviatura }}" required maxlength="10"
                               class="w-16 bg-transparent text-sm font-mono text-slate-500 rounded px-1 py-0.5 border border-transparent focus:border-teal-brand focus:bg-white outline-none transition">
                        <span class="text-[11px] text-slate-400 shrink-0">{{ $u->productos_count }} prod.</span>
                        <button type="submit" title="Guardar cambio" class="shrink-0 p-1.5 rounded text-slate-300 hover:text-teal-brand opacity-0 group-hover:opacity-100 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('catalogos.unidades.destroy', $u) }}"
                          onsubmit="return confirm('¿Eliminar la unidad «{{ $u->nombre }}»?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Eliminar" class="p-1.5 rounded text-slate-300 hover:text-red-500 transition" @disabled($u->productos_count > 0)>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-slate-400 text-center">Sin unidades aún.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
