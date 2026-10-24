<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ModuleService;
use App\Support\TenantContext;
use App\Support\PermissionCatalog;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(ModuleService::class);
    }

    public function boot(): void
    {
        // Fechas en español (zona horaria configurada en config/app.php)
        Carbon::setLocale(config('app.locale'));

        // Tablas paginadas con estilos de Bootstrap 5
        Paginator::useBootstrapFive();

        // HTTPS obligatorio en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Política de contraseñas para todo el sistema
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        // Una Gate por cada permiso del catálogo: @can('usuarios.gestionar'), middleware can:...
        foreach (PermissionCatalog::slugs() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // Límite para solicitudes de recuperación de contraseña (por IP)
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        // Límite de reportes públicos por IP, institución y hora (configurable por institución)
        RateLimiter::for('public-report', function (Request $request) {
            // Se resuelve desde la ruta: el limitador puede ejecutarse antes que el middleware de institución
            $param = $request->route('institution');
            $institution = $param instanceof \App\Models\Institution ? $param
                : ($param ? \App\Models\Institution::where('slug', $param)->first() : \App\Models\Institution::default());
            $limit = $institution?->public_report_hourly_limit ?? (int) config('seguridad_escolar.reportes_publicos.limite_por_hora', 5);

            return Limit::perHour(max(1, $limit))->by($request->ip().'|'.($institution?->id ?? 0))
                ->response(fn () => response()->view('errors.429', [], 429));
        });

        // Consulta pública de estado: limita intentos para impedir adivinar claves
        RateLimiter::for('status-lookup', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // @module('dashboard') ... @endmodule
        Blade::if('module', fn (string $key) => app(ModuleService::class)->enabled($key));
    }
}
