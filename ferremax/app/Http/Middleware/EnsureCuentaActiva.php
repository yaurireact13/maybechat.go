<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión en curso si el usuario fue desactivado o si su ferretería
 * fue suspendida después de iniciar sesión. Sin esto, una sesión abierta
 * seguiría operando aunque el Super Admin haya bloqueado el acceso.
 */
class EnsureCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $empresaSuspendida = $user->empresa_id && optional($user->empresa)->activo === false;

            if ($user->activo === false || $empresaSuspendida) {
                // Un super admin impersonando conserva su acceso: se le devuelve a su panel.
                $superId = $request->session()->get('impersonator_id');

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $mensaje = $empresaSuspendida
                    ? 'El acceso de tu ferretería está suspendido. Comunícate con soporte.'
                    : 'Tu usuario fue desactivado. Comunícate con el administrador.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $mensaje], 401);
                }

                if ($superId) {
                    return redirect()->route('login')->withErrors(['email' => 'La ferretería impersonada ya no está activa. Inicia sesión nuevamente.']);
                }

                return redirect()->route('login')->withErrors(['email' => $mensaje]);
            }
        }

        return $next($request);
    }
}
