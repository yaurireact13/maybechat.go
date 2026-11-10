@extends('layouts.superadmin')

@section('titulo', 'Usuarios')

@section('contenido')
<div class="mb-5">
    <h2 class="text-2xl font-extrabold text-plat">Usuarios de todas las ferreterías</h2>
    <p class="text-sm text-slate-500">{{ $totalUsuarios }} usuarios · <span class="text-emerald-600 font-semibold">{{ $activos }} activos</span></p>
</div>

<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
    <div class="relative lg:col-span-2">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o correo"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
    </div>
    <select name="empresa_id" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
        <option value="">Todas las ferreterías</option>
        @foreach ($empresas as $e)
            <option value="{{ $e->id }}" @selected(request('empresa_id') == $e->id)>{{ $e->nombre }}</option>
        @endforeach
    </select>
    <select name="rol" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
        <option value="">Todos los roles</option>
        @foreach (['admin', 'vendedor', 'almacenero', 'cajero'] as $r)
            <option value="{{ $r }}" @selected(request('rol') == $r)>{{ ucfirst($r) }}</option>
        @endforeach
    </select>
    <div class="flex gap-2">
        <select name="estado" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
            <option value="">Todos</option>
            <option value="activos" @selected(request('estado') == 'activos')>Activos</option>
            <option value="inactivos" @selected(request('estado') == 'inactivos')>Inactivos</option>
        </select>
        <button class="bg-plat hover:bg-plat-light text-white text-sm font-bold rounded-lg px-4 py-2 transition">Filtrar</button>
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100 bg-slate-50">
                    <th class="px-5 py-3 font-bold">Usuario</th>
                    <th class="px-4 py-3 font-bold">Ferretería</th>
                    <th class="px-4 py-3 font-bold">Rol</th>
                    <th class="px-4 py-3 font-bold text-center">Estado</th>
                    <th class="px-5 py-3 font-bold text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($usuarios as $u)
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-plat">{{ $u->name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $u->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $u->empresa->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $u->rol }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($u->activo)
                                <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Activo</span>
                            @else
                                <span class="text-[11px] font-bold px-2 py-1 rounded-full bg-slate-200 text-slate-600">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('superadmin.usuarios.toggle', $u) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs font-bold px-3 py-1.5 rounded-lg {{ $u->activo ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }} transition">
                                        {{ $u->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                <details class="relative">
                                    <summary class="list-none cursor-pointer text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Resetear</summary>
                                    <div class="absolute right-0 mt-2 w-60 bg-white border border-slate-200 rounded-lg shadow-xl p-3 z-10">
                                        <form method="POST" action="{{ route('superadmin.usuarios.reset', $u) }}" class="space-y-2">
                                            @csrf @method('PATCH')
                                            <label class="block text-[11px] font-bold text-slate-500">Nueva contraseña</label>
                                            <input type="text" name="password" minlength="6" required placeholder="mín. 6 caracteres"
                                                   class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-plat-accent outline-none">
                                            <button class="w-full bg-plat-accent hover:opacity-90 text-white text-xs font-bold py-1.5 rounded-lg">Guardar contraseña</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No se encontraron usuarios con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-5">{{ $usuarios->links() }}</div>
@endsection
