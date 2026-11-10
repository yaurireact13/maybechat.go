@extends('layouts.app')

@section('titulo', 'Facturación Electrónica')

@section('contenido')
@php
    $estados = [
        'aceptado'  => ['Aceptados', 'text-emerald-600'],
        'observado' => ['Observados', 'text-amber-600'],
        'rechazado' => ['Rechazados', 'text-red-600'],
        'error'     => ['Con error', 'text-red-600'],
        'pendiente' => ['Pendientes', 'text-slate-500'],
    ];
@endphp

@php
    $paisInfo = [
        'PE' => ['Perú', 'SUNAT', 'XML UBL 2.1 + CDR'],
        'CO' => ['Colombia', 'DIAN', 'UBL 2.1 + CUFE'],
        'CL' => ['Chile', 'SII', 'DTE + timbre'],
        'AR' => ['Argentina', 'ARCA', 'WSFE + CAE'],
        'MX' => ['México', 'SAT', 'CFDI 4.0'],
    ][$empresa->factura_pais] ?? ['Perú', 'SUNAT', 'XML UBL 2.1 + CDR'];
    [$paisNombre, $organismo, $estandar] = $paisInfo;
@endphp

<div class="max-w-4xl mx-auto">

    <!-- Banner del módulo -->
    <div class="rounded-xl bg-gradient-to-r from-navy to-navy-light text-white p-5 mb-5 shadow-lg shadow-navy/20 flex flex-col sm:flex-row sm:items-center gap-4">
        <span class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
            <svg class="w-7 h-7 text-lime-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-lg font-extrabold leading-tight">Facturación Electrónica — {{ $paisNombre }} ({{ $organismo }})</p>
            <p class="text-sm text-slate-200 mt-0.5">Emite comprobantes válidos ante {{ $organismo }}: boletas y facturas en formato {{ $estandar }}.</p>
        </div>
        <span class="self-start sm:self-center shrink-0 text-xs font-bold bg-lime-brand text-navy px-3 py-1.5 rounded-full">
            {{ $paisNombre }} · {{ $organismo }}
        </span>
    </div>

    <div class="mb-5">
        <h1 class="text-2xl font-extrabold text-navy">Configuración</h1>
        <p class="text-sm text-slate-500">Configura cómo tu ferretería emite comprobantes electrónicos</p>
    </div>

    <!-- Estado global de la plataforma -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $global['enabled'] ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
            <span class="text-slate-600">Servicio {{ $global['enabled'] ? 'habilitado' : 'deshabilitado' }} en la plataforma</span>
        </div>
        <div class="text-slate-400">Microservicio: <span class="font-mono text-slate-600">{{ $global['base_url'] ?: 'no configurado' }}</span></div>
        <div class="text-slate-400">Token: {{ $global['con_token'] ? '✓ configurado' : '— (modo simulado)' }}</div>
    </div>

    <!-- Resumen de comprobantes -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Total</p>
            <p class="text-2xl font-extrabold text-navy mt-1">{{ $total }}</p>
        </div>
        @foreach ($estados as $k => [$label, $cls])
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                <p class="text-2xl font-extrabold {{ $cls }} mt-1">{{ $stats[$k] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    <!-- Formulario -->
    <form method="POST" action="{{ route('facturacion_config.update') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        @csrf @method('PUT')

        <p class="text-xs font-bold uppercase tracking-wide text-teal-dark border-b border-slate-100 pb-2">Ajustes generales</p>

        <label class="flex items-start gap-3">
            <input type="checkbox" name="factura_activa" value="1" @checked($empresa->factura_activa)
                   class="mt-1 rounded border-slate-300 text-teal-brand focus:ring-teal-brand">
            <span>
                <span class="block font-semibold text-slate-700">Emitir comprobantes electrónicos</span>
                <span class="block text-xs text-slate-500">Al cerrar una boleta o factura en el POS, se emite el comprobante automáticamente.</span>
            </span>
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Modo</label>
                <select name="factura_modo" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    <option value="simulado" @selected($empresa->factura_modo === 'simulado')>Simulado (pruebas, sin envío real)</option>
                    <option value="real" @selected($empresa->factura_modo === 'real')>Real (envía al organismo vía microservicio)</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">En «simulado» el comprobante se acepta localmente para probar el flujo.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">País / organismo</label>
                <select name="factura_pais" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    @foreach (['PE' => 'Perú — SUNAT', 'CO' => 'Colombia — DIAN', 'CL' => 'Chile — SII', 'AR' => 'Argentina — ARCA', 'MX' => 'México — SAT'] as $k => $v)
                        <option value="{{ $k }}" @selected($empresa->factura_pais === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Perú está disponible; los demás se habilitan por fases.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Serie de facturas</label>
                <input type="text" name="factura_serie_factura" value="{{ old('factura_serie_factura', $empresa->factura_serie_factura) }}" maxlength="8"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Serie de boletas</label>
                <input type="text" name="factura_serie_boleta" value="{{ old('factura_serie_boleta', $empresa->factura_serie_boleta) }}" maxlength="8"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
        </div>

        <!-- ===== Datos del emisor ===== -->
        <p class="text-xs font-bold uppercase tracking-wide text-teal-dark border-b border-slate-100 pb-2 pt-2">Datos del emisor (SUNAT)</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">RUC del emisor</label>
                <input type="text" name="ruc" value="{{ old('ruc', $config->ruc ?? $empresa->ruc) }}" maxlength="20"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Razón social</label>
                <input type="text" name="razon_social" value="{{ old('razon_social', $config->razon_social ?? $empresa->nombre) }}" maxlength="255"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre comercial</label>
                <input type="text" name="nombre_comercial" value="{{ old('nombre_comercial', $config->nombre_comercial) }}" maxlength="255"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ubigeo (código)</label>
                <input type="text" name="ubigeo" value="{{ old('ubigeo', $config->ubigeo) }}" maxlength="10" placeholder="150101"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección fiscal</label>
                <input type="text" name="direccion_fiscal" value="{{ old('direccion_fiscal', $config->direccion_fiscal ?? $empresa->direccion) }}" maxlength="255"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
        </div>

        <!-- ===== Ambiente y credenciales ===== -->
        <p class="text-xs font-bold uppercase tracking-wide text-teal-dark border-b border-slate-100 pb-2 pt-2">Ambiente y credenciales SUNAT</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ambiente</label>
                <select name="ambiente" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                    <option value="beta" @selected(($config->ambiente ?? 'beta') === 'beta')>Beta / Homologación (pruebas SUNAT)</option>
                    <option value="produccion" @selected(($config->ambiente ?? '') === 'produccion')>Producción (emisión real)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Usuario SOL</label>
                <input type="text" name="usuario_sol" value="{{ old('usuario_sol', $config->usuario_sol) }}" maxlength="100" autocomplete="off"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Clave SOL</label>
                <input type="password" name="clave_sol" value="" autocomplete="new-password"
                       placeholder="{{ ($config->exists ?? false) && $config->clave_sol ? '•••••••• (guardada)' : 'Ingresa la clave SOL' }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                <p class="text-[11px] text-slate-400 mt-1">Se guarda cifrada. Déjala vacía para conservar la actual.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Vencimiento del certificado</label>
                <input type="date" name="certificado_vence" value="{{ old('certificado_vence', optional($config->certificado_vence ?? null)->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Certificado digital (.pfx / .p12 / .pem)</label>
                <input type="file" name="certificado" accept=".pfx,.p12,.pem"
                       class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                @if (($config->exists ?? false) && $config->tieneCertificado())
                    <p class="text-[11px] text-emerald-600 mt-1">Certificado cargado: {{ $config->certificado_nombre }}
                        @if ($config->certificadoVencido()) <span class="text-red-500 font-bold">(VENCIDO)</span> @endif
                    </p>
                @else
                    <p class="text-[11px] text-slate-400 mt-1">Aún no hay certificado cargado.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Clave del certificado</label>
                <input type="password" name="clave_certificado" value="" autocomplete="new-password"
                       placeholder="{{ ($config->exists ?? false) && $config->clave_certificado ? '•••••••• (guardada)' : 'Clave del .pfx' }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-brand focus:ring-2 focus:ring-teal-brand/30 outline-none">
                <p class="text-[11px] text-slate-400 mt-1">Se guarda cifrada. Déjala vacía para conservar la actual.</p>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700 flex items-start gap-2">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            Las credenciales (clave SOL y del certificado) se almacenan <b>cifradas</b>. El certificado se guarda fuera de la carpeta pública. La URL y el token del microservicio los define la plataforma.
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-teal-brand hover:bg-teal-dark text-white font-bold px-6 py-2.5 rounded-lg shadow-lg shadow-teal-brand/30 transition">
                Guardar configuración
            </button>
        </div>
    </form>
</div>
@endsection
