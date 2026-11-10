@php
    $mensaje = $exception->getMessage();
    // Los mensajes por defecto de Laravel vienen en inglés: se reemplazan por uno claro.
    if (! $mensaje || str_contains($mensaje, 'unauthorized') || $mensaje === 'Forbidden') {
        $mensaje = 'Tu rol no tiene permiso para entrar a esta sección. Si lo necesitas, pídeselo al administrador de tu ferretería.';
    }
    $usuario = auth()->user();
    $inicio = $usuario?->esSuperAdmin() ? route('superadmin.dashboard') : ($usuario ? route('dashboard') : route('login'));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Acceso restringido — FerreMax</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="font-sans bg-slate-100 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-6 sm:p-8 text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
        </div>
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Error 403</p>
        <h1 class="text-xl sm:text-2xl font-extrabold text-navy mt-1">Acceso restringido</h1>
        <p class="text-sm text-slate-500 mt-3">{{ $mensaje }}</p>
        @if ($usuario && $usuario->empresa_id)
            <p class="text-xs text-slate-400 mt-3">Tu rol: <b class="text-slate-600">{{ $usuario->rol_nombre }}</b></p>
        @endif
        <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
            <a href="{{ $inicio }}" class="inline-flex justify-center items-center bg-teal-brand hover:bg-teal-dark text-white text-sm font-bold px-5 py-2.5 rounded-lg transition">Ir al inicio</a>
            <button type="button" onclick="history.back()" class="inline-flex justify-center items-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold px-5 py-2.5 rounded-lg transition">Volver</button>
        </div>
    </main>
</body>
</html>
