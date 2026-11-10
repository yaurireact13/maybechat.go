<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Permite el acceso solo a usuarios con rol superadmin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->esSuperAdmin()) {
            abort(403, 'Acceso exclusivo del panel de plataforma.');
        }

        return $next($request);
    }
}
