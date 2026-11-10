@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
<div class="max-w-2xl mx-auto">
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold text-navy">Configuración</h1>
        <p class="text-sm text-slate-500">Datos de tu empresa y parámetros del sistema</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('configuracion.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <h2 class="font-bold text-navy text-sm uppercase tracking-wide mb-4">Datos de la empresa</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre comercial <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', $empresa->nombre) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">RUC</label>
                    <input type="text" name="ruc" value="{{ old('ruc', $empresa->ruc) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $empresa->telefono) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección</label>
                    <input type="text" name="direccion" value="{{ old('direccion', $empresa->direccion) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email de contacto</label>
                    <input type="email" name="email" value="{{ old('email', $empresa->email) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                </div>
            </div>
            <p class="text-xs text-slate-400 mt-3">Estos datos aparecen en los tickets y cotizaciones impresas.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <h2 class="font-bold text-navy text-sm uppercase tracking-wide mb-4">Parámetros de venta</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Símbolo de moneda <span class="text-red-500">*</span></label>
                    <input type="text" name="moneda" value="{{ old('moneda', $empresa->moneda) }}" required maxlength="10"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    <p class="text-[11px] text-slate-400 mt-1">Ej: S/, $, Bs, Q</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Impuesto / IGV (%) <span class="text-red-500">*</span></label>
                    <input type="number" name="impuesto" value="{{ old('impuesto', $empresa->impuesto + 0) }}" required step="0.01" min="0" max="100"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    <p class="text-[11px] text-slate-400 mt-1">Se aplica en POS, compras y cotizaciones.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <h2 class="font-bold text-navy text-sm uppercase tracking-wide mb-3">Plan actual</h2>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold text-teal-brand uppercase">Plan {{ $empresa->plan }}</span>
                    <p class="text-xs text-slate-400 mt-1">Empresa registrada el {{ $empresa->created_at->format('d/m/Y') }}</p>
                </div>
                <span class="text-xs font-semibold bg-teal-brand/10 text-teal-dark px-3 py-1.5 rounded-full">Activo</span>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-8 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>
@endsection
