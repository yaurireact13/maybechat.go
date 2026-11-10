<?php

namespace App\Http\Controllers;

use App\Models\IaConsulta;
use App\Services\Ia\IaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Asistente de reportes con IA (solo administrador de la ferretería).
 * Pregunta en lenguaje natural → SQL de solo lectura sobre vistas filtradas por empresa → tabla + gráfico + resumen.
 */
class AsistenteController extends Controller
{
    private function svc(): IaService
    {
        $s = new IaService(auth()->user());
        IaService::instalarVistas(); // crea las vistas de lectura si faltan

        return $s;
    }

    private function out(array $d): JsonResponse
    {
        return response()->json($d + ['ok' => true], 200, [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function fail(string $msg, int $code = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $msg], $code, [], JSON_UNESCAPED_UNICODE);
    }

    private function consultas()
    {
        return IaConsulta::where('empresa_id', auth()->user()->empresa_id)->where('user_id', auth()->id());
    }

    public function index()
    {
        return view('asistente.index');
    }

    public function estado(): JsonResponse
    {
        try {
            $s = $this->svc();
        } catch (Throwable $e) {
            return $this->fail('No se pudieron preparar las vistas del asistente: ' . $e->getMessage(), 500);
        }
        $c = $s->cfg();
        $fav = $this->consultas()->where('favorito', true)->orderByDesc('ultimo_uso')->get(['id', 'pregunta', 'titulo']);
        $his = $this->consultas()->where('favorito', false)->orderByDesc('ultimo_uso')->limit(15)->get(['id', 'pregunta', 'titulo', 'favorito']);

        return $this->out([
            'cfg' => ['proveedor' => $c['proveedor'], 'url' => $c['url'], 'modelo' => $c['modelo'], 'clave_puesta' => $c['clave'] !== '', 'resumen' => $c['resumen']],
            'listo' => $s->listo($c),
            'presets' => IaService::presets(),
            'favoritos' => $fav,
            'historial' => $his,
        ]);
    }

    public function guardarConfig(Request $request): JsonResponse
    {
        $presets = IaService::presets();
        $p = (string) $request->input('proveedor');
        if (! isset($presets[$p])) {
            return $this->fail('Proveedor no válido.');
        }
        $url = trim((string) $request->input('url'));
        $modelo = trim((string) $request->input('modelo'));
        if (! preg_match('#^https?://[^\s]+$#i', $url)) {
            return $this->fail('La URL debe empezar por http:// o https://');
        }
        if ($modelo === '' || mb_strlen($modelo) > 100) {
            return $this->fail('Indica el nombre del modelo.');
        }
        $k = trim((string) $request->input('clave', ''));
        if ($k !== '' && $k !== '__borrar__' && (mb_strlen($k) < 8 || mb_strlen($k) > 300 || preg_match('/\s/', $k))) {
            return $this->fail('La clave no parece válida.');
        }
        (new IaService(auth()->user()))->guardarConfig($p, $url, $modelo, (bool) $request->input('resumen'), $k);

        return $this->out([]);
    }

    public function probar(): JsonResponse
    {
        try {
            $t = (new IaService(auth()->user()))->llamar([['role' => 'user', 'content' => 'Responde solo con la palabra OK.']], 20);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->out(['respuesta' => mb_substr(trim($t), 0, 60)]);
    }

    public function preguntar(Request $request): JsonResponse
    {
        $pregunta = trim((string) $request->input('pregunta'));
        if (mb_strlen($pregunta) < 4) {
            return $this->fail('Escribe tu pregunta (mínimo 4 caracteres).');
        }
        if (mb_strlen($pregunta) > 500) {
            return $this->fail('La pregunta es demasiado larga (máx. 500 caracteres).');
        }
        // Límite sencillo: 30 consultas cada 10 minutos por sesión
        $now = time();
        $hist = array_values(array_filter(session('ia_hist', []), fn ($t) => $t > $now - 600));
        if (count($hist) >= 30) {
            return $this->fail('Demasiadas consultas seguidas. Espera unos minutos.', 429);
        }
        $hist[] = $now;
        session(['ia_hist' => $hist]);

        $previo = is_array($request->input('previo')) ? $request->input('previo') : null;
        try {
            $s = $this->svc();
            $r = $s->preguntar($pregunta, $previo);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
        if (isset($r['mensaje'])) {
            return $this->out(['mensaje' => $r['mensaje']]);
        }

        $u = auth()->user();
        $c = IaConsulta::create([
            'empresa_id' => $u->empresa_id, 'user_id' => $u->id, 'pregunta' => $pregunta, 'titulo' => $r['titulo'],
            'sql_texto' => $r['sql'], 'grafico' => json_encode($r['grafico']), 'ultimo_uso' => now(),
        ]);
        // Conserva solo las 60 consultas recientes no favoritas
        $viejas = $this->consultas()->where('favorito', false)->orderByDesc('id')->skip(60)->take(1000)->pluck('id');
        if ($viejas->isNotEmpty()) {
            IaConsulta::whereIn('id', $viejas)->delete();
        }

        return $this->out(['id' => $c->id] + $r);
    }

    /** Vuelve a ejecutar una consulta guardada, sin gastar IA. */
    public function ejecutar(Request $request): JsonResponse
    {
        $c = $this->consultas()->findOrFail((int) $request->input('id'));
        try {
            $r = $this->svc()->ejecutar($c->sql_texto);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
        $c->update(['veces' => $c->veces + 1, 'ultimo_uso' => now()]);

        return $this->out([
            'id' => $c->id, 'pregunta' => $c->pregunta, 'titulo' => $c->titulo ?: $c->pregunta,
            'grafico' => json_decode((string) $c->grafico, true) ?: ['tipo' => 'none'], 'favorito' => (int) $c->favorito,
        ] + $r);
    }

    public function resumir(Request $request): JsonResponse
    {
        $s = new IaService(auth()->user());
        if (! $s->cfg()['resumen']) {
            return $this->fail('El resumen está desactivado.');
        }
        $c = $this->consultas()->findOrFail((int) $request->input('id'));
        try {
            $this->svc();
            $r = $s->ejecutar($c->sql_texto);
            if (! $r['filas']) {
                return $this->out(['resumen' => 'La consulta no devolvió resultados para ese período.']);
            }
            $t = $s->resumir($c->pregunta, $r['columnas'], $r['filas']);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }

        return $this->out(['resumen' => $t]);
    }

    public function favorito(Request $request): JsonResponse
    {
        $c = $this->consultas()->findOrFail((int) $request->input('id'));
        $v = (bool) $request->input('valor');
        $c->update(['favorito' => $v]);

        return $this->out(['favorito' => (int) $v]);
    }

    public function borrar(Request $request): JsonResponse
    {
        $this->consultas()->findOrFail((int) $request->input('id'))->delete();

        return $this->out([]);
    }

    public function csv(int $id)
    {
        $c = $this->consultas()->findOrFail($id);
        try {
            $r = $this->svc()->ejecutar($c->sql_texto);
        } catch (Throwable $e) {
            abort(400, $e->getMessage());
        }
        $titulo = preg_replace('/[^a-z0-9]+/i', '_', (string) (iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) ($c->titulo ?: 'reporte')) ?: 'reporte'));
        $nombre = 'consulta_' . trim($titulo, '_') . '_' . date('Ymd') . '.csv';
        $safe = fn ($v) => is_string($v) && $v !== '' && str_contains("=+-@\t\r", $v[0]) ? "'" . $v : $v; // evita fórmulas en Excel

        return response()->streamDownload(function () use ($r, $safe) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            fputcsv($o, $r['columnas'], ';');
            foreach ($r['filas'] as $f) {
                fputcsv($o, array_map($safe, $f), ';');
            }
            fclose($o);
        }, $nombre, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function esquema()
    {
        return response($this->svc()->esquemaTexto(), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
