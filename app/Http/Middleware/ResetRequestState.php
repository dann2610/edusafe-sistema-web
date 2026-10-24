<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que ningún estado de institución o de módulos quede de una
 * petición anterior (importante en pruebas y en servidores persistentes).
 */
class ResetRequestState
{
    public function handle(Request $request, Closure $next): Response
    {
        app(TenantContext::class)->clear();
        app(ModuleService::class)->flush();

        try {
            return $next($request);
        } finally {
            app(TenantContext::class)->clear();
        }
    }
}
