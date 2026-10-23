<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a cambiar la contraseña temporal antes de usar el sistema.
 * Las rutas de cambio de contraseña y de cierre de sesión quedan exentas.
 */
class EnsurePasswordIsChanged
{
    private const EXEMPT_ROUTES = ['password.change', 'password.change.update', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs(...self::EXEMPT_ROUTES)) {
            return redirect()->route('password.change')
                ->with('warning', 'Por seguridad, debe cambiar la contraseña temporal antes de continuar.');
        }

        return $next($request);
    }
}
