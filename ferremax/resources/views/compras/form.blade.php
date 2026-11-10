@extends('layouts.app')

@section('titulo', 'Nueva Compra')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('compras.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-navy transition" title="Volver">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
    </a>
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Nueva Compra</h1>
        <p class="text-sm text-slate-500">Registra el ingreso de mercadería de un proveedor</p>
    </div>
</div>

@if ($errors->any())
    <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('compras.store') }}" id="formCompra">
    @csrf
    <input type="hidden" name="items" id="inputItems">

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">

        <!-- ===== Izquierda: datos + buscador ===== -->
        <div class="xl:col-span-2 space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Proveedor <span class="text-red-500">*</span></label>
                        <select name="proveedor_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                            <option value="">— Selecciona un proveedor —</option>
                            @foreach ($proveedores as $pr)
                                <option value="{{ $pr->id }}" @selected(old('proveedor_id') == $pr->id)>{{ $pr->razon_social }}{{ $pr->ruc ? ' — RUC ' . $pr->ruc : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha" required value="{{ old('fecha', now()->format('Y-m-d')) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    </div>
                </div>
                <label class="flex items-center gap-2 mt-4 text-sm text-slate-600">
                    <input type="checkbox" name="actualizar_precios" value="1" checked class="rounded border-slate-300 text-teal-brand focus:ring-teal-brand">
                    Actualizar el precio de compra de los productos con el costo de esta compra
                </label>
                <label class="flex items-center gap-2 mt-3 text-sm text-slate-600">
                    <input type="checkbox" name="a_credito" id="chkCredito" value="1" @checked(old('a_credito')) class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    Compra a crédito (queda como cuenta por pagar al proveedor)
                </label>
                <div id="bloqueVencimiento" class="mt-3 {{ old('a_credito') ? '' : 'hidden' }}">
                    <label class="block text-sm font-semibold text-amber-600 mb-1.5">Fecha de vencimiento del pago</label>
                    <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', now()->addDays(30)->format('Y-m-d')) }}"
                           class="w-full sm:w-60 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm focus:border-amber-500 outline-none">
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="relative">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" id="buscarProducto" placeholder="Buscar producto por nombre o código para agregar..." autocomplete="off"
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
                    <div id="sugerencias" class="hidden absolute z-10 left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-64 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Ítems -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                                <th class="px-4 py-3 font-bold">Producto</th>
                                <th class="px-2 py-3 font-bold text-center" style="width:110px">Cantidad</th>
                                <th class="px-2 py-3 font-bold text-center" style="width:130px">Costo unit.</th>
                                <th class="px-2 py-3 font-bold text-right" style="width:110px">Subtotal</th>
                                <th class="px-3 py-3" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="tablaItems" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <p id="sinItems" class="text-center text-slate-400 text-sm py-10">Busca y agrega los productos recibidos</p>
            </div>
        </div>

        <!-- ===== Derecha: resumen ===== -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 xl:sticky xl:top-20 space-y-4">
            <h2 class="font-bold text-navy">Resumen</h2>
            <div class="bg-slate-50 rounded-lg p-3 space-y-1 text-sm">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span id="lblSubtotal">{{ $moneda }} 0.00</span></div>
                <div class="flex justify-between text-slate-500"><span>IGV ({{ auth()->user()->empresa->impuesto + 0 }}%)</span><span id="lblImpuesto">{{ $moneda }} 0.00</span></div>
                <div class="flex justify-between font-extrabold text-navy text-lg pt-1 border-t border-slate-200"><span>TOTAL</span><span id="lblTotal">{{ $moneda }} 0.00</span></div>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">Al guardar, el stock de cada producto aumentará y se registrará una entrada en el kardex.</p>
            <button type="submit" id="btnGuardar" disabled
                    class="w-full bg-teal-brand hover:bg-teal-dark disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold py-3 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                Registrar Compra
            </button>
            <a href="{{ route('compras.index') }}" class="block text-center text-sm font-bold text-slate-500 hover:text-navy transition">Cancelar</a>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const MONEDA = @json($moneda);
    const IMPUESTO = {{ (float) (auth()->user()->empresa->impuesto ?? 18) }};
    const PRODUCTOS = @json($productos);
    let items = {};

    const buscar = document.getElementById('buscarProducto');
    const sugerencias = document.getElementById('sugerencias');

    // Mostrar el vencimiento solo en compras a crédito
    const chkCredito = document.getElementById('chkCredito');
    const bloqueVenc = document.getElementById('bloqueVencimiento');
    if (chkCredito) {
        chkCredito.addEventListener('change', () => bloqueVenc.classList.toggle('hidden', !chkCredito.checked));
    }

    function fmt(n) { return MONEDA + ' ' + n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    buscar.addEventListener('input', () => {
        const q = buscar.value.trim().toLowerCase();
        if (!q) { sugerencias.classList.add('hidden'); return; }
        const lista = PRODUCTOS.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)).slice(0, 8);
        if (!lista.length) { sugerencias.classList.add('hidden'); return; }
        sugerencias.innerHTML = lista.map(p => `
            <button type="button" onclick="agregar(${p.id})"
                class="w-full text-left px-4 py-2.5 hover:bg-teal-brand/5 flex items-center justify-between gap-2 border-b border-slate-50 last:border-0">
                <span>
                    <span class="block text-sm font-semibold text-slate-700">${p.nombre}</span>
                    <span class="text-[10px] font-mono text-slate-400">${p.codigo} · stock actual: ${p.stock}</span>
                </span>
                <span class="text-xs font-bold text-navy shrink-0">${fmt(p.precio_compra)}</span>
            </button>`).join('');
        sugerencias.classList.remove('hidden');
    });

    document.addEventListener('click', e => {
        if (!sugerencias.contains(e.target) && e.target !== buscar) sugerencias.classList.add('hidden');
    });

    function agregar(id) {
        const p = PRODUCTOS.find(x => x.id === id);
        if (!p) return;
        if (items[id]) items[id].cantidad++;
        else items[id] = { id: p.id, nombre: p.nombre, codigo: p.codigo, unidad: p.unidad, cantidad: 1, precio: p.precio_compra };
        buscar.value = '';
        sugerencias.classList.add('hidden');
        render();
        buscar.focus();
    }

    function actualizar(id, campo, valor) {
        const item = items[id];
        if (!item) return;
        const v = parseFloat(valor) || 0;
        if (campo === 'cantidad' && v <= 0) { delete items[id]; }
        else item[campo] = v;
        render();
    }

    function quitar(id) { delete items[id]; render(); }

    function render() {
        const lista = Object.values(items);
        const tbody = document.getElementById('tablaItems');
        document.getElementById('sinItems').style.display = lista.length ? 'none' : '';
        tbody.innerHTML = lista.map(i => `
            <tr>
                <td class="px-4 py-2.5">
                    <p class="text-xs font-semibold text-slate-700">${i.nombre}</p>
                    <p class="text-[10px] font-mono text-slate-400">${i.codigo} · ${i.unidad}</p>
                </td>
                <td class="px-2 py-2.5 text-center">
                    <input type="number" value="${i.cantidad}" min="0.01" step="0.01"
                           onchange="actualizar(${i.id}, 'cantidad', this.value)"
                           class="w-full text-center text-sm font-bold border border-slate-200 rounded-lg py-1.5 outline-none focus:border-teal-brand">
                </td>
                <td class="px-2 py-2.5 text-center">
                    <input type="number" value="${i.precio}" min="0" step="0.01"
                           onchange="actualizar(${i.id}, 'precio', this.value)"
                           class="w-full text-center text-sm border border-slate-200 rounded-lg py-1.5 outline-none focus:border-teal-brand">
                </td>
                <td class="px-2 py-2.5 text-right text-sm font-bold text-navy">${fmt(i.cantidad * i.precio)}</td>
                <td class="px-3 py-2.5 text-center">
                    <button type="button" onclick="quitar(${i.id})" class="w-7 h-7 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 text-sm">✕</button>
                </td>
            </tr>`).join('');

        const total = lista.reduce((s, i) => s + i.cantidad * i.precio, 0);
        const sub = total / (1 + IMPUESTO / 100);
        document.getElementById('lblSubtotal').textContent = fmt(sub);
        document.getElementById('lblImpuesto').textContent = fmt(total - sub);
        document.getElementById('lblTotal').textContent = fmt(total);
        document.getElementById('btnGuardar').disabled = lista.length === 0;
    }

    document.getElementById('formCompra').addEventListener('submit', () => {
        document.getElementById('inputItems').value = JSON.stringify(Object.values(items).map(i => ({ id: i.id, cantidad: i.cantidad, precio: i.precio })));
    });

    render();
</script>
@endpush
