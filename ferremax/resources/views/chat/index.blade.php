@extends('layouts.app')

@section('titulo', 'Chat IA')

@section('contenido')
<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Chat IA</h1>
        <p class="text-sm text-slate-500">Conversa con tu sistema: pregunta por productos, ventas, clientes, proveedores, stock o cómo usar cada módulo</p>
    </div>
    <a class="ia-soft" href="{{ route('asistente.index') }}"><span data-ico="gear"></span> Configurar IA</a>
</div>
<div id="chatPage"></div>
<script>document.addEventListener('DOMContentLoaded', () => window.IAU && IAU.hydrate());</script>
@endsection
