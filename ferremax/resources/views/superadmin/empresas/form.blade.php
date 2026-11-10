@extends('layouts.superadmin')

@section('titulo', $empresa->exists ? 'Editar ferretería' : 'Nueva ferretería')

@section('contenido')
@php $editar = $empresa->exists; @endphp

<div class="flex items-center gap-3 mb-5">
    <a href="{{ $editar ? route('superadmin.empresas.show', $empresa) : route('superadmin.empresas.index') }}" class="p-2 rounded-lg hover:bg-slate-200 text-slate-500">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
    </a>
    <h2 class="text-2xl font-extrabold text-plat">{{ $editar ? 'Editar ' . $empresa->nombre : 'Registrar nueva ferretería' }}</h2>
</div>

<form method="POST" action="{{ $editar ? route('superadmin.empresas.update', $empresa) : route('superadmin.empresas.store') }}" class="max-w-3xl space-y-5">
    @csrf
    @if ($editar) @method('PUT') @endif

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold text-plat mb-4">Datos de la ferretería</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" value="{{ old('nombre', $empresa->nombre) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent focus:ring-2 focus:ring-plat-accent/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">RUC</label>
                <input type="text" name="ruc" value="{{ old('ruc', $empresa->ruc) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $empresa->telefono) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección</label>
                <input type="text" name="direccion" value="{{ old('direccion', $empresa->direccion) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
            </div>
            @if ($editar)
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Correo de contacto</label>
                    <input type="email" name="email" value="{{ old('email', $empresa->email) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-bold text-plat mb-4">Suscripción</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Plan <span class="text-red-500">*</span></label>
                <select name="plan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
                    @foreach (['basico' => 'Básico — $49', 'pro' => 'Pro — $99', 'premium' => 'Premium — $199'] as $k => $v)
                        <option value="{{ $k }}" @selected(old('plan', $empresa->plan) == $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Vencimiento del plan</label>
                <input type="date" name="plan_vence" value="{{ old('plan_vence', optional($empresa->plan_vence)->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Moneda <span class="text-red-500">*</span></label>
                <input type="text" name="moneda" value="{{ old('moneda', $empresa->moneda ?? 'S/') }}" required maxlength="10"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Impuesto (%) <span class="text-red-500">*</span></label>
                <input type="number" name="impuesto" step="0.01" min="0" max="100" value="{{ old('impuesto', $empresa->impuesto ?? 18) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
            </div>
        </div>
    </div>

    @if (! $editar)
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-plat mb-1">Administrador inicial</h3>
            <p class="text-xs text-slate-500 mb-4">Esta cuenta podrá iniciar sesión y gestionar la ferretería.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre del administrador <span class="text-red-500">*</span></label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Correo <span class="text-red-500">*</span></label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
                    <input type="text" name="admin_password" value="{{ old('admin_password') }}" required minlength="6"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-plat-accent outline-none">
                </div>
            </div>
        </div>
    @endif

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-plat-accent hover:opacity-90 text-white font-bold px-6 py-2.5 rounded-lg shadow-lg shadow-plat-accent/30 transition">
            {{ $editar ? 'Guardar cambios' : 'Crear ferretería' }}
        </button>
        <a href="{{ $editar ? route('superadmin.empresas.show', $empresa) : route('superadmin.empresas.index') }}" class="text-sm font-bold text-slate-500 hover:text-plat">Cancelar</a>
    </div>
</form>
@endsection
