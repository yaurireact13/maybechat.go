@extends('layouts.superadmin')

@section('titulo', 'Ferreterías')

@section('contenido')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    <div>
        <h2 class="text-2xl font-extrabold text-plat">Ferreterías (tenants)</h2>
        <p class="text-sm text-slate-500">{{ $totalEmpresas }} en total · <span class="text-emerald-600 font-semibold">{{ $activas }} activas</span></p>
    </div>
    <a href="{{ route('superadmin.empresas.create') }}" class="inline-flex items-center justify-center gap-2 bg-plat-accent hover:opacity-90 text-white text-sm font-bold px-4 py-2.5 rounded-lg shadow-lg shadow-plat-accent/30 transition w-full sm:w-fit">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Nueva ferretería
    </a>
</div>

<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="relative lg:col-span-2">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, RUC o correo"
               class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
    </div>
    <select name="plan" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
        <option value="">Todos los planes</option>
        @foreach (['basico' => 'Básico', 'pro' => 'Pro', 'premium' => 'Premium'] as $k => $v)
            <option value="{{ $k }}" @selected(request('plan') == $k)>{{ $v }}</option>
        @endforeach
    </select>
    <div class="flex gap-2">
        <select name="estado" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-plat-accent/40 outline-none">
            <option value="">Todas</option>
            <option value="activas" @selected(request('estado') == 'activas')>Activas</option>
            <option value="suspendidas" @selected(request('estado') == 'suspendidas')>Suspendidas</option>
        </select>
        <button class="bg-plat hover:bg-plat-light text-white text-sm font-bold rounded-lg px-4 py-2 transition">Filtrar</button>
    </div>
</form>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse ($empresas as $e)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <a href="{{ route('superadmin.empresas.show', $e) }}" class="font-bold text-plat hover:text-plat-accent truncate block">{{ $e->nombre }}</a>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $e->ruc ? 'RUC ' . $e->ruc : 'Sin RUC' }}</p>
                </div>
                @if ($e->activo)
                    <span class="shrink-0 text-[11px] font-bold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Activa</span>
                @else
                    <span class="shrink-0 text-[11px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-700">Suspendida</span>
                @endif
            </div>

            <div class="grid grid-cols-3 gap-2 my-4 text-center">
                <div class="bg-slate-50 rounded-lg py-2">
                    <p class="text-lg font-extrabold text-plat">{{ $e->users_count }}</p>
                    <p class="text-[10px] text-slate-400 uppercase">Usuarios</p>
                </div>
                <div class="bg-slate-50 rounded-lg py-2">
                    <p class="text-lg font-extrabold text-plat">{{ $e->productos_count }}</p>
                    <p class="text-[10px] text-slate-400 uppercase">Productos</p>
                </div>
                <div class="bg-slate-50 rounded-lg py-2">
                    <p class="text-sm font-extrabold text-plat-accent capitalize mt-1">{{ $e->plan }}</p>
                    <p class="text-[10px] text-slate-400 uppercase">Plan</p>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-auto pt-2">
                <a href="{{ route('superadmin.empresas.show', $e) }}" class="flex-1 text-center text-xs font-bold text-plat bg-slate-100 hover:bg-slate-200 rounded-lg py-2 transition">Ver detalle</a>
                <form method="POST" action="{{ route('superadmin.empresas.impersonar', $e) }}">
                    @csrf
                    <button type="submit" title="Impersonar" class="px-3 py-2 rounded-lg bg-plat-accent/10 text-plat-accent hover:bg-plat-accent hover:text-white transition" {{ $e->activo ? '' : 'disabled' }}>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="md:col-span-2 xl:col-span-3 bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-400">
            No se encontraron ferreterías con esos filtros.
        </div>
    @endforelse
</div>

<div class="mt-5">{{ $empresas->links() }}</div>
@endsection
