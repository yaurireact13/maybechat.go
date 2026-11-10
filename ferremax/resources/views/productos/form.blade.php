@extends('layouts.app')

@section('titulo', $producto->exists ? 'Editar Producto' : 'Nuevo Producto')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('productos.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-navy transition" title="Volver">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-navy">{{ $producto->exists ? 'Editar Producto' : 'Nuevo Producto' }}</h1>
            @if ($producto->exists)
                <p class="text-sm text-slate-500">{{ $producto->codigo }} — {{ $producto->nombre }}</p>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            <p class="font-bold mb-1">Corrige los siguientes errores:</p>
            <ul class="list-disc ml-5 space-y-0.5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $producto->exists ? route('productos.update', $producto) : route('productos.store') }}"
          class="space-y-5">
        @csrf
        @if ($producto->exists) @method('PUT') @endif

        <!-- Identificación -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <h2 class="font-bold text-navy text-sm uppercase tracking-wide mb-4">Identificación</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Código interno <span class="text-red-500">*</span></label>
                    <input type="text" name="codigo" value="{{ old('codigo', $producto->codigo) }}" required placeholder="P-016"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Código de barras</label>
                    <input type="text" name="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras) }}" placeholder="7501234567890"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre del producto <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" required placeholder="Martillo de uña 16oz"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Descripción</label>
                    <textarea name="descripcion" rows="2" placeholder="Detalles adicionales, especificaciones técnicas..."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">{{ old('descripcion', $producto->descripcion) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Clasificación -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-navy text-sm uppercase tracking-wide">Clasificación</h2>
                <a href="{{ route('catalogos.index') }}" class="text-xs font-semibold text-teal-brand hover:text-teal-dark">Administrar catálogos →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Categoría</label>
                    <select name="categoria_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        <option value="">— Sin categoría —</option>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}" @selected(old('categoria_id', $producto->categoria_id) == $c->id)>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Marca</label>
                    <select name="marca_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        <option value="">— Sin marca —</option>
                        @foreach ($marcas as $m)
                            <option value="{{ $m->id }}" @selected(old('marca_id', $producto->marca_id) == $m->id)>{{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Unidad de medida</label>
                    <select name="unidad_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                        <option value="">— Sin unidad —</option>
                        @foreach ($unidades as $u)
                            <option value="{{ $u->id }}" @selected(old('unidad_id', $producto->unidad_id) == $u->id)>{{ $u->nombre }} ({{ $u->abreviatura }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Precios y stock -->
        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <h2 class="font-bold text-navy text-sm uppercase tracking-wide mb-4">Precios y Stock</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Precio compra ({{ $moneda }}) <span class="text-red-500">*</span></label>
                    <input type="number" name="precio_compra" id="precio_compra" step="0.01" min="0" required
                           value="{{ old('precio_compra', $producto->precio_compra) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Precio venta ({{ $moneda }}) <span class="text-red-500">*</span></label>
                    <input type="number" name="precio_venta" id="precio_venta" step="0.01" min="0" required
                           value="{{ old('precio_venta', $producto->precio_venta) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    <p id="margen" class="text-[11px] text-slate-400 mt-1"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Stock actual <span class="text-red-500">*</span></label>
                    <input type="number" name="stock" step="0.01" min="0" required
                           value="{{ old('stock', $producto->stock + 0) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    @if ($producto->exists)
                        <p class="text-[11px] text-amber-600 mt-1">Si cambias el stock se registrará un ajuste en el kardex.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Stock mínimo <span class="text-red-500">*</span></label>
                    <input type="number" name="stock_minimo" step="0.01" min="0" required
                           value="{{ old('stock_minimo', $producto->stock_minimo + 0) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ubicación en almacén</label>
                    <input type="text" name="ubicacion" value="{{ old('ubicacion', $producto->ubicacion) }}" placeholder="Pasillo 3, Estante B"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('productos.index') }}"
               class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-slate-50 transition">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-8 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                {{ $producto->exists ? 'Guardar Cambios' : 'Crear Producto' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Margen de ganancia en vivo
    const pc = document.getElementById('precio_compra');
    const pv = document.getElementById('precio_venta');
    const margen = document.getElementById('margen');

    function calcularMargen() {
        const compra = parseFloat(pc.value) || 0;
        const venta = parseFloat(pv.value) || 0;
        if (compra > 0 && venta > 0) {
            const pct = ((venta - compra) / compra * 100).toFixed(1);
            margen.textContent = `Margen: ${pct}% (ganancia {{ $moneda }} ${(venta - compra).toFixed(2)} por unidad)`;
            margen.className = venta >= compra ? 'text-[11px] text-emerald-600 mt-1 font-semibold' : 'text-[11px] text-red-500 mt-1 font-semibold';
        } else {
            margen.textContent = '';
        }
    }
    pc.addEventListener('input', calcularMargen);
    pv.addEventListener('input', calcularMargen);
    calcularMargen();
</script>
@endpush
