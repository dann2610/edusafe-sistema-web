<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión si la cuenta fue desactivada, no tiene rol o su
 * institución fue suspendida o desactivada, aunque ya hubiera iniciado sesión.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $message = match (true) {
            ! $user->is_active => __('auth.session_closed_inactive'),
            $user->role_id === null => __('auth.no_role'),
            $user->institution_id === null && ! $user->isSuperadmin() => __('auth.no_role'),
            $user->institution_id !== null && ! $user->institution?->status->allowsStaffLogin() => __('auth.institution_unavailable'),
            default => null,
        };

        if ($message) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
