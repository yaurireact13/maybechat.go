<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotencia a nivel HTTP: si el ERP reintenta una emision con la misma
 * cabecera "Idempotency-Key", se devuelve la respuesta previamente almacenada
 * en lugar de emitir dos veces (evita duplicados ante timeouts/reintentos).
 */
final class Idempotencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $clave = $request->header('Idempotency-Key');

        if (! $clave) {
            return $next($request); // opcional: exigirla en emision
        }

        $previa = DB::table('idempotency_keys')->where('clave', $clave)->first();
        if ($previa) {
            return response($previa->respuesta, $previa->status)->header('Content-Type', 'application/json');
        }

        /** @var Response $response */
        $response = $next($request);

        DB::table('idempotency_keys')->insert([
            'clave'     => $clave,
            'status'    => $response->getStatusCode(),
            'respuesta' => $response->getContent(),
            'creado_en' => now(),
        ]);

        return $response;
    }
}
