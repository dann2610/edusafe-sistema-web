<?php

namespace App\Http\Middleware;

use App\Models\Institution;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Páginas públicas: resuelve la institución desde la URL (/i/{slug}/...) o,
 * en las rutas sin slug, la institución por defecto. Solo las instituciones
 * activas tienen página pública.
 */
class ResolvePublicInstitution
{
    public function handle(Request $request, Closure $next): Response
    {
        $param = $request->route('institution');
        $institution = $param instanceof Institution ? $param
            : ($param ? Institution::where('slug', $param)->first() : Institution::default());

        if (! $institution || ! $institution->status->isPublic()) {
            abort(404);
        }

        app(TenantContext::class)->set($institution);
        View::share('publicInstitution', $institution);

        return $next($request);
    }
}
