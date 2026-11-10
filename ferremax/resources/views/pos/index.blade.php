@extends('layouts.app')

@section('titulo', 'Punto de Venta')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

@if (session('venta_id'))
    <div class="mb-4 flex flex-wrap items-center gap-3 bg-teal-brand/10 border border-teal-brand/30 rounded-lg px-4 py-3">
        <p class="text-sm font-semibold text-teal-dark">¿Deseas imprimir el comprobante de la venta?</p>
        <a href="{{ route('pos.recibo', session('venta_id')) }}" target="_blank"
           class="inline-flex items-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-xs font-bold px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" /></svg>
            Imprimir ticket
        </a>
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">

    <!-- ===== Catálogo ===== -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    <input type="text" id="buscarPos" placeholder="Buscar o escanear código de barras... (F2)" autofocus autocomplete="off"
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
                </div>
                <select id="filtroCategoria" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="gridProductos" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 max-h-[65vh] overflow-y-auto pr-1"></div>
        <p id="sinResultados" class="hidden text-center text-slate-400 text-sm py-10">No se encontraron productos.</p>
    </div>

    <!-- ===== Carrito ===== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm xl:sticky xl:top-20">
        <div class="px-5 pt-5 pb-3 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-navy">Carrito <span id="numItems" class="text-xs font-semibold text-slate-400"></span></h2>
            <button onclick="vaciarCarrito()" class="text-xs font-semibold text-red-400 hover:text-red-600 transition">Vaciar</button>
        </div>

        <div id="carritoItems" class="divide-y divide-slate-100 max-h-72 overflow-y-auto"></div>
        <p id="carritoVacio" class="text-center text-slate-400 text-sm py-10">Agrega productos del catálogo</p>

        <form method="POST" action="{{ route('pos.store') }}" id="formVenta" class="p-5 border-t border-slate-100 space-y-3">
            @csrf
            <input type="hidden" name="items" id="inputItems">

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Cliente</label>
                    <select name="cliente_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        <option value="">Cliente Varios</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}{{ $c->numero_documento ? ' — ' . $c->numero_documento : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Comprobante</label>
                    <select name="tipo_comprobante" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        <option value="ticket">Ticket</option>
                        <option value="boleta">Boleta</option>
                        <option value="factura">Factura</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Método de pago</label>
                    <select name="metodo_pago" id="selMetodoPago" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Descuento ({{ $moneda }})</label>
                    <input type="number" name="descuento" id="inputDescuento" step="0.01" min="0" value="0"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
                <div class="col-span-2 hidden" id="bloqueVencimiento">
                    <label class="block text-xs font-bold text-amber-600 mb-1">Vence el (cuenta por cobrar)</label>
                    <input type="date" name="fecha_vencimiento" id="inputVencimiento"
                           value="{{ now()->addDays(30)->format('Y-m-d') }}"
                           class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm focus:border-amber-500 outline-none">
                    <p class="text-[11px] text-amber-600 mt-1">Requiere seleccionar un cliente registrado.</p>
                </div>
            </div>

            <div class="bg-slate-50 rounded-lg p-3 space-y-1 text-sm">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span id="lblSubtotal">{{ $moneda }} 0.00</span></div>
                <div class="flex justify-between text-slate-500"><span>IGV ({{ auth()->user()->empresa->impuesto + 0 }}%)</span><span id="lblImpuesto">{{ $moneda }} 0.00</span></div>
                <div class="flex justify-between text-slate-500" id="filaDescuento" style="display:none"><span>Descuento</span><span id="lblDescuento" class="text-red-500"></span></div>
                <div class="flex justify-between font-extrabold text-navy text-lg pt-1 border-t border-slate-200"><span>TOTAL</span><span id="lblTotal">{{ $moneda }} 0.00</span></div>
            </div>

            <button type="submit" id="btnCobrar" disabled
                    class="w-full bg-teal-brand hover:bg-teal-dark disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold py-3 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                Cobrar (F9)
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const MONEDA = @json($moneda);
    const IMPUESTO = {{ (float) (auth()->user()->empresa->impuesto ?? 18) }};
    const PRODUCTOS = @json($productos);
    let carrito = {};

    const grid = document.getElementById('gridProductos');
    const buscar = document.getElementById('buscarPos');
    const filtroCat = document.getElementById('filtroCategoria');

    // Mostrar el campo de vencimiento solo en ventas a crédito
    const selMetodo = document.getElementById('selMetodoPago');
    const bloqueVenc = document.getElementById('bloqueVencimiento');
    if (selMetodo) {
        selMetodo.addEventListener('change', () => {
            bloqueVenc.classList.toggle('hidden', selMetodo.value !== 'credito');
        });
    }

    function fmt(n) { return MONEDA + ' ' + n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function disponible(p) { return p.stock - (carrito[p.id]?.cantidad || 0); }

    function renderGrid() {
        const q = buscar.value.trim().toLowerCase();
        const cat = filtroCat.value;
        const lista = PRODUCTOS.filter(p =>
            (!cat || p.categoria_id == cat) &&
            (!q || p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q) || (p.codigo_barras || '').toLowerCase().includes(q))
        );
        document.getElementById('sinResultados').classList.toggle('hidden', lista.length > 0);
        grid.innerHTML = lista.map(p => {
            const disp = disponible(p);
            const sinStock = disp <= 0;
            return `<button type="button" onclick="agregar(${p.id})" ${sinStock ? 'disabled' : ''}
                class="text-left bg-white rounded-xl border border-slate-200 p-3 hover:border-teal-brand hover:shadow-md transition ${sinStock ? 'opacity-50 cursor-not-allowed' : ''}">
                <div class="flex items-start justify-between gap-1">
                    <p class="text-[10px] font-mono text-slate-400">${p.codigo}</p>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full ${disp <= 5 ? 'bg-red-100 text-red-500' : 'bg-lime-brand/20 text-lime-700'}">${disp}</span>
                </div>
                <p class="text-xs font-semibold text-slate-700 leading-snug mt-1 line-clamp-2" style="min-height:2rem">${p.nombre}</p>
                <p class="text-sm font-extrabold text-navy mt-1.5">${fmt(p.precio)} <span class="text-[10px] text-slate-400 font-semibold">/ ${p.unidad}</span></p>
            </button>`;
        }).join('');
    }

    function agregar(id) {
        const p = PRODUCTOS.find(x => x.id === id);
        if (!p || disponible(p) <= 0) return;
        if (carrito[id]) carrito[id].cantidad++;
        else carrito[id] = { id: p.id, nombre: p.nombre, codigo: p.codigo, precio: p.precio, stock: p.stock, unidad: p.unidad, cantidad: 1 };
        renderTodo();
    }

    function cambiarCantidad(id, delta) {
        const item = carrito[id];
        if (!item) return;
        item.cantidad += delta;
        if (item.cantidad <= 0) delete carrito[id];
        else if (item.cantidad > item.stock) item.cantidad = item.stock;
        renderTodo();
    }

    function fijarCantidad(id, valor) {
        const item = carrito[id];
        if (!item) return;
        let v = parseFloat(valor) || 0;
        if (v <= 0) delete carrito[id];
        else item.cantidad = Math.min(v, item.stock);
        renderTodo();
    }

    function quitar(id) { delete carrito[id]; renderTodo(); }

    function vaciarCarrito() {
        if (Object.keys(carrito).length && !confirm('¿Vaciar el carrito?')) return;
        carrito = {};
        renderTodo();
    }

    function renderCarrito() {
        const cont = document.getElementById('carritoItems');
        const items = Object.values(carrito);
        document.getElementById('carritoVacio').style.display = items.length ? 'none' : '';
        document.getElementById('numItems').textContent = items.length ? `(${items.length} ítems)` : '';
        cont.innerHTML = items.map(i => `
            <div class="px-5 py-3 flex items-center gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-700 truncate">${i.nombre}</p>
                    <p class="text-[10px] text-slate-400">${fmt(i.precio)} × ${i.cantidad} = <b class="text-navy">${fmt(i.precio * i.cantidad)}</b></p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" onclick="cambiarCantidad(${i.id}, -1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm">−</button>
                    <input type="number" value="${i.cantidad}" min="0" max="${i.stock}" step="1"
                           onchange="fijarCantidad(${i.id}, this.value)"
                           class="w-12 text-center text-sm font-bold border border-slate-200 rounded-lg py-1 outline-none focus:border-teal-brand">
                    <button type="button" onclick="cambiarCantidad(${i.id}, 1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm">+</button>
                    <button type="button" onclick="quitar(${i.id})" class="w-7 h-7 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 text-sm">✕</button>
                </div>
            </div>`).join('');
    }

    function renderTotales() {
        const items = Object.values(carrito);
        const bruto = items.reduce((s, i) => s + i.precio * i.cantidad, 0);
        let desc = parseFloat(document.getElementById('inputDescuento').value) || 0;
        desc = Math.min(desc, bruto);
        const total = bruto - desc;
        const sub = total / (1 + IMPUESTO / 100);
        document.getElementById('lblSubtotal').textContent = fmt(sub);
        document.getElementById('lblImpuesto').textContent = fmt(total - sub);
        document.getElementById('lblTotal').textContent = fmt(total);
        document.getElementById('filaDescuento').style.display = desc > 0 ? '' : 'none';
        document.getElementById('lblDescuento').textContent = '− ' + fmt(desc);
        document.getElementById('btnCobrar').disabled = items.length === 0;
    }

    function renderTodo() { renderGrid(); renderCarrito(); renderTotales(); }

    buscar.addEventListener('input', renderGrid);
    filtroCat.addEventListener('change', renderGrid);
    document.getElementById('inputDescuento').addEventListener('input', renderTotales);

    // Escáner: Enter en el buscador agrega coincidencia exacta de código de barras o código
    buscar.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = buscar.value.trim().toLowerCase();
        if (!q) return;
        const p = PRODUCTOS.find(x => (x.codigo_barras || '').toLowerCase() === q || x.codigo.toLowerCase() === q);
        if (p) { agregar(p.id); buscar.value = ''; renderGrid(); }
    });

    // Atajos: F2 buscar, F9 cobrar
    document.addEventListener('keydown', e => {
        if (e.key === 'F2') { e.preventDefault(); buscar.focus(); buscar.select(); }
        if (e.key === 'F9') { e.preventDefault(); if (!document.getElementById('btnCobrar').disabled) document.getElementById('formVenta').requestSubmit(); }
    });

    document.getElementById('formVenta').addEventListener('submit', () => {
        document.getElementById('inputItems').value = JSON.stringify(Object.values(carrito).map(i => ({ id: i.id, cantidad: i.cantidad, precio: i.precio })));
    });

    renderTodo();
</script>
@endpush
