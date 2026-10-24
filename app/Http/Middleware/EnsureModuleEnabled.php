<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas: ->middleware('module:dashboard').
 * Si el módulo no está activo para la institución, la ruta no existe (404):
 * desactivar un módulo afecta realmente al servidor, no solo al menú.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $service = app(ModuleService::class);
        foreach ($modules as $module) {
            if (! $service->enabled($module)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
