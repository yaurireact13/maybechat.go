@extends('layouts.app')

@section('titulo', 'Asistente IA')

@section('contenido')
<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-extrabold text-navy">Asistente de reportes</h1>
        <p class="text-sm text-slate-500">Pregunta en lenguaje natural y obtén tablas, gráficos y resúmenes de tu ferretería</p>
    </div>
    <div class="flex gap-2">
        <button type="button" class="ia-soft" id="btnEsq"><span data-ico="db"></span> Qué datos ve la IA</button>
        <button type="button" class="ia-soft" id="btnCfg"><span data-ico="gear"></span> Configurar IA</button>
    </div>
</div>

<div class="ia-hero">
    <h2><span data-ico="sparkles" data-cls="w-6 h-6"></span> ¿Qué quieres saber de tu negocio?</h2>
    <p>Escribe tu pregunta como se la harías a un analista. Ejemplo: «¿Cuáles fueron los 10 productos más vendidos este mes?»</p>
    <form class="ia-ask" id="frmAsk">
        <textarea id="iaQ" rows="1" maxlength="500" placeholder="Escribe tu pregunta aquí…" aria-label="Pregunta"></textarea>
        <button type="submit" id="iaGo"><span data-ico="send"></span> Preguntar</button>
    </form>
    <div class="ia-chips" id="iaChips"></div>
</div>

<div id="iaSetup" class="ia-card mb-4" hidden>
    <h6 class="font-bold text-navy mb-3 flex items-center gap-2"><span data-ico="sparkles" data-cls="w-5 h-5 text-teal-brand"></span> Activa el asistente en 2 minutos (gratis)</h6>
    <div class="ia-step"><b>1</b><div class="text-sm text-slate-700">Entra a <a class="text-teal-dark underline" href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a> con tu cuenta de Google y crea una clave gratuita.</div></div>
    <div class="ia-step"><b>2</b>
        <div class="flex-1 text-sm text-slate-700">Pega la clave aquí y pulsa <b>Activar</b>:
            <form class="flex gap-2 mt-2 flex-wrap" id="frmQuick">
                <input type="password" class="ia-input" id="qKey" style="max-width:340px" placeholder="Clave de Gemini" autocomplete="new-password">
                <button class="ia-btn" id="qGo">Activar</button>
            </form>
            <div class="text-xs text-red-600 mt-1" id="qErr"></div>
        </div>
    </div>
    <p class="text-xs text-slate-500 mb-2">También puedes usar Groq, OpenRouter u Ollama (local, sin enviar datos a internet) desde la configuración avanzada.</p>
    <button type="button" class="ia-soft" onclick="document.getElementById('btnCfg').click()"><span data-ico="gear"></span> Configuración avanzada</button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 min-w-0">
        <div id="iaOut">
            <div class="ia-card text-center text-slate-500 py-12">
                <span data-ico="chat" data-cls="w-10 h-10 mx-auto text-slate-300"></span>
                <div class="mt-2">Aquí aparecerán tus resultados</div>
                <div class="text-xs">Puedes hacer preguntas de seguimiento, como «ahora por cajero».</div>
            </div>
        </div>
    </div>
    <div class="space-y-5">
        <div class="ia-card ia-side">
            <h6 class="font-bold text-navy mb-2 flex items-center gap-2"><span data-ico="star" data-fill="1" data-cls="w-4 h-4 text-amber-400"></span> Favoritas</h6>
            <div id="iaFav"></div>
        </div>
        <div class="ia-card ia-side">
            <h6 class="font-bold text-navy mb-2 flex items-center gap-2"><span data-ico="clock"></span> Recientes</h6>
            <div id="iaHis"></div>
        </div>
    </div>
</div>

{{-- Modal: configuración --}}
<div class="ia-modal" id="mdlCfg" hidden>
    <div>
        <form id="frmCfg" novalidate>
            <div class="flex items-center justify-between px-6 pt-5">
                <h5 class="font-extrabold text-navy text-lg flex items-center gap-2"><span data-ico="gear" data-cls="w-5 h-5"></span> Configurar el asistente de IA</h5>
                <button type="button" class="text-slate-400 hover:text-slate-700" data-close="mdlCfg" aria-label="Cerrar"><span data-ico="x" data-cls="w-5 h-5"></span></button>
            </div>
            <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-12">
                    <label class="ia-lbl">Proveedor</label>
                    <select class="ia-select" id="cProv"></select>
                    <div class="text-xs text-slate-500 mt-1" id="cAyuda"></div>
                </div>
                <div class="md:col-span-7"><label class="ia-lbl">URL de la API</label><input class="ia-input" id="cUrl" autocomplete="off"></div>
                <div class="md:col-span-5"><label class="ia-lbl">Modelo</label><input class="ia-input" id="cMod" autocomplete="off"></div>
                <div class="md:col-span-12">
                    <label class="ia-lbl">Clave (API key)</label>
                    <input type="password" class="ia-input" id="cKey" autocomplete="new-password">
                    <div class="text-xs text-slate-500 mt-1" id="cKeyH"></div>
                </div>
                <div class="md:col-span-12">
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer"><input type="checkbox" id="cRes" class="w-4 h-4 accent-teal-600"> Generar un resumen en lenguaje natural de cada resultado (usa una segunda consulta a la IA)</label>
                </div>
                <div class="md:col-span-12">
                    <div class="border border-slate-200 rounded-lg p-3 text-xs text-slate-600 bg-slate-50"><b class="text-navy">Privacidad y seguridad.</b> La IA solo ve la estructura de unas vistas de lectura de <b>tu ferretería</b> (sin contraseñas ni usuarios) y su consulta se valida antes de ejecutarse: solo SELECT, en modo lectura, con límite de tiempo y de filas. Al proveedor se envía tu pregunta y, si activas el resumen, hasta 25 filas del resultado (pueden incluir nombres de productos o clientes). Con <b>Ollama</b> nada sale de tu PC. La clave se guarda cifrada.</div>
                </div>
                <div class="md:col-span-12 text-sm text-red-600" id="cErr"></div>
            </div>
            <div class="flex items-center gap-2 px-6 pb-5">
                <button type="button" class="ia-soft mr-auto" id="cTest"><span data-ico="bolt"></span> Probar conexión</button>
                <button type="button" class="ia-soft" data-close="mdlCfg">Cerrar</button>
                <button type="submit" class="ia-btn"><span data-ico="check"></span> Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: datos que ve la IA --}}
<div class="ia-modal" id="mdlEsq" hidden>
    <div>
        <div class="flex items-center justify-between px-6 pt-5">
            <h5 class="font-extrabold text-navy text-lg flex items-center gap-2"><span data-ico="db" data-cls="w-5 h-5"></span> Datos que ve la IA</h5>
            <button type="button" class="text-slate-400 hover:text-slate-700" data-close="mdlEsq" aria-label="Cerrar"><span data-ico="x" data-cls="w-5 h-5"></span></button>
        </div>
        <div class="px-6 py-4">
            <p class="text-sm text-slate-500 mb-2">Solo estas vistas de lectura, filtradas por tu empresa. Nunca accede a las tablas reales, usuarios ni contraseñas.</p>
            <pre class="ia-sql" id="esqTxt" style="margin:0"></pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/asistente.js') }}?v=1"></script>
@endpush
