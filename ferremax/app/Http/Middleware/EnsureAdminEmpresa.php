<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminEmpresa
{
    /** Solo el administrador de la ferretería accede (Asistente IA y Chat IA). */
    public function handle(Request $request, Closure $next): Response
    {
        $u = auth()->user();
        abort_unless($u && $u->esAdmin() && $u->empresa_id, 403, 'Solo el administrador puede usar el asistente de IA.');

        return $next($request);
    }
}
