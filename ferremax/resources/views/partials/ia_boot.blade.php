{{-- Asistente IA / Chat IA: configuración JS + burbuja flotante (solo administrador de la ferretería) --}}
<script>
    window.IA = {
        csrf: @json(csrf_token()),
        moneda: @json(auth()->user()->empresa->moneda ?? 'S/'),
        u: {
            estado: @json(route('asistente.estado')),
            config: @json(route('asistente.config')),
            probar: @json(route('asistente.probar')),
            preguntar: @json(route('asistente.preguntar')),
            ejecutar: @json(route('asistente.ejecutar')),
            resumir: @json(route('asistente.resumir')),
            favorito: @json(route('asistente.favorito')),
            borrar: @json(route('asistente.borrar')),
            csv: @json(url('/asistente/csv')),
            esquema: @json(route('asistente.esquema')),
            chatEstado: @json(route('chat.estado')),
            chatEnviar: @json(route('chat.enviar')),
            chatLimpiar: @json(route('chat.limpiar')),
        },
    };
</script>
<script src="{{ asset('js/ia-common.js') }}?v=1"></script>
<script src="{{ asset('js/chat-ia.js') }}?v=1"></script>
