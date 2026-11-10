<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Área de la ferretería (tenant). Si un super admin entra aquí
     * sin estar impersonando, se le redirige a su panel de plataforma.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->esSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return $next($request);
    }
}
