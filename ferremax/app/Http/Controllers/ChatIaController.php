<?php

namespace App\Http\Controllers;

use App\Services\Ia\IaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Chat IA: conversa con el sistema (datos del negocio + ayuda de uso). Solo administrador.
 * El historial vive en la sesión del servidor.
 */
class ChatIaController extends Controller
{
    private function out(array $d): JsonResponse
    {
        return response()->json($d + ['ok' => true], 200, [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function fail(string $msg, int $code = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $msg], $code, [], JSON_UNESCAPED_UNICODE);
    }

    public function index()
    {
        return view('chat.index');
    }

    public function estado(): JsonResponse
    {
        $s = new IaService(auth()->user());
        $c = $s->cfg();

        return $this->out([
            'listo' => $s->listo($c),
            'proveedor' => IaService::presets()[$c['proveedor']]['nombre'],
            'empresa' => auth()->user()->empresa->nombre ?? 'tu ferretería',
            'historial' => array_map(fn ($h) => ['q' => $h['q'], 'a' => $h['a']], session('chat_ia', [])),
        ]);
    }

    public function enviar(Request $request): JsonResponse
    {
        $msg = trim((string) $request->input('mensaje'));
        if (mb_strlen($msg) < 2) {
            return $this->fail('Escribe tu mensaje.');
        }
        if (mb_strlen($msg) > 400) {
            return $this->fail('El mensaje es demasiado largo (máx. 400 caracteres).');
        }
        $now = time();
        $t = array_values(array_filter(session('chat_ia_t', []), fn ($x) => $x > $now - 600));
        if (count($t) >= 40) {
            return $this->fail('Demasiados mensajes seguidos. Espera unos minutos.', 429);
        }
        $t[] = $now;
        session(['chat_ia_t' => $t]);

        $hist = session('chat_ia', []);
        try {
            IaService::instalarVistas();
            $r = (new IaService(auth()->user()))->chat($msg, $hist);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
        $hist[] = ['q' => $msg, 'sql' => $r['sql'] ?? null, 'a' => mb_substr($r['texto'], 0, 800)];
        session(['chat_ia' => array_slice($hist, -12)]);

        return $this->out(['texto' => $r['texto'], 'tabla' => $r['tabla'] ?? null, 'sql' => $r['sql'] ?? null]);
    }

    public function limpiar(): JsonResponse
    {
        session(['chat_ia' => []]);

        return $this->out([]);
    }
}
