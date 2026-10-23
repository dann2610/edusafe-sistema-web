<?php

namespace App\Providers;

use App\Models\User;
use App\Support\PermissionCatalog;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
    }
}
