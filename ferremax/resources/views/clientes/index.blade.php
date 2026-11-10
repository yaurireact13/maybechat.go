@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
@php $moneda = auth()->user()->empresa->moneda ?? 'S/'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Clientes</h1>
        <p class="text-sm text-slate-500">{{ $clientes->total() }} {{ $clientes->total() == 1 ? 'cliente' : 'clientes' }}</p>
    </div>
    <button onclick="abrirModal()"
            class="inline-flex items-center justify-center gap-2 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nuevo Cliente
    </button>
</div>

<form method="GET" action="{{ route('clientes.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, documento o teléfono"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
    </div>
    <select name="estado" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-brand/40 outline-none">
        <option value="activos" @selected(request('estado', 'activos') === 'activos')>Activos</option>
        <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
    </select>
    <button type="submit" class="bg-navy hover:bg-navy-light text-white text-sm font-bold px-6 py-2 rounded-lg transition">Buscar</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Cliente</th>
                    <th class="px-3 py-3 font-bold hidden sm:table-cell">Documento</th>
                    <th class="px-3 py-3 font-bold hidden md:table-cell">Contacto</th>
                    <th class="px-3 py-3 font-bold text-center hidden lg:table-cell">Compras</th>
                    <th class="px-3 py-3 font-bold text-right">Total comprado</th>
                    <th class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($clientes as $c)
                    <tr class="hover:bg-slate-50 {{ !$c->activo ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-slate-700">{{ $c->nombre }}</p>
                            @if ($c->direccion)<p class="text-[11px] text-slate-400 truncate max-w-[200px]">{{ $c->direccion }}</p>@endif
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden sm:table-cell">
                            @if ($c->numero_documento){{ $c->tipo_documento }} {{ $c->numero_documento }}@else — @endif
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">
                            @if ($c->telefono)<p>{{ $c->telefono }}</p>@endif
                            @if ($c->email)<p class="text-slate-400">{{ $c->email }}</p>@endif
                            @if (!$c->telefono && !$c->email) — @endif
                        </td>
                        <td class="px-3 py-3 text-center text-slate-600 font-bold hidden lg:table-cell">{{ $c->ventas_count }}</td>
                        <td class="px-3 py-3 text-right font-bold text-navy">{{ $moneda }} {{ number_format($c->total_comprado ?? 0, 2) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('ventas.index', ['buscar' => $c->numero_documento ?: $c->nombre]) }}" title="Ver sus ventas"
                                   class="p-2 rounded-lg text-slate-400 hover:text-navy hover:bg-slate-100 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" /></svg>
                                </a>
                                <button type="button" title="Editar"
                                        onclick='abrirModal(@json($c->only(["id","nombre","tipo_documento","numero_documento","telefono","email","direccion"])))'
                                        class="p-2 rounded-lg text-slate-400 hover:text-teal-brand hover:bg-teal-brand/10 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                </button>
                                @if ($c->activo)
                                    <form method="POST" action="{{ route('clientes.destroy', $c) }}"
                                          onsubmit="return confirm('¿Desactivar al cliente «{{ $c->nombre }}»?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Desactivar" class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('clientes.activar', $c) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Reactivar" class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No se encontraron clientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($clientes->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">{{ $clientes->links() }}</div>
    @endif
</div>

<!-- Modal -->
<div id="modalCliente" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <h2 id="tituloModal" class="text-lg font-extrabold text-navy mb-4">Nuevo Cliente</h2>
        <form method="POST" id="formCliente" action="{{ route('clientes.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="metodoForm" value="POST">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre / Razón social <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" id="f_nombre" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tipo doc.</label>
                    <select name="tipo_documento" id="f_tipo_documento" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                        <option value="DNI">DNI</option>
                        <option value="RUC">RUC</option>
                        <option value="CE">Carnet Ext.</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">N° documento</label>
                    <input type="text" name="numero_documento" id="f_numero_documento"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono</label>
                    <input type="text" name="telefono" id="f_telefono"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="f_email"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección</label>
                <input type="text" name="direccion" id="f_direccion"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand outline-none">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="cerrarModal()"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 text-sm font-bold hover:bg-slate-50 transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-4 py-2.5 rounded-lg transition">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const URL_STORE = @json(route('clientes.store'));
    const URL_UPDATE = @json(url('/clientes')) + '/';

    function abrirModal(cliente = null) {
        const form = document.getElementById('formCliente');
        document.getElementById('tituloModal').textContent = cliente ? 'Editar Cliente' : 'Nuevo Cliente';
        document.getElementById('metodoForm').value = cliente ? 'PUT' : 'POST';
        form.action = cliente ? URL_UPDATE + cliente.id : URL_STORE;
        ['nombre', 'tipo_documento', 'numero_documento', 'telefono', 'email', 'direccion'].forEach(campo => {
            document.getElementById('f_' + campo).value = cliente ? (cliente[campo] ?? '') : (campo === 'tipo_documento' ? 'DNI' : '');
        });
        document.getElementById('modalCliente').classList.remove('hidden');
        document.getElementById('f_nombre').focus();
    }

    function cerrarModal() { document.getElementById('modalCliente').classList.add('hidden'); }
    @if ($errors->any()) abrirModal(); @endif
</script>
@endpush
