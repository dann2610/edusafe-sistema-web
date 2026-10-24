<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Hash de referencia para igualar el tiempo de respuesta cuando el correo no existe. */
    private const DUMMY_HASH = '$2y$12$fRiY7vH5kVaS0cud.Fxjf.5S8yliZqREjkHRPKJ/MBAcZPSFjKq4.';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Valida credenciales, estado de la cuenta y límite de intentos.
     *
     * @throws ValidationException
     */
    public function authenticate(AuditLogger $audit): User
    {
        $this->ensureIsNotRateLimited($audit);

        $user = User::findByEmail($this->string('email'));
        $valid = Hash::check($this->string('password'), $user?->password ?? self::DUMMY_HASH) && $user !== null;

        if (! $valid) {
            RateLimiter::hit($this->throttleKey(), $this->decaySeconds());
            $audit->failed('inicio_sesion_fallido', $user, ['motivo' => 'credenciales_invalidas'], $user);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $reason = match (true) {
            ! $user->is_active => 'cuenta_desactivada',
            $user->role_id === null => 'sin_rol',
            $user->institution_id === null && ! $user->isSuperadmin() => 'sin_rol',
            $user->institution_id !== null && ! $user->institution?->status->allowsStaffLogin() => 'institucion_no_disponible',
            default => null,
        };
        if ($reason) {
            RateLimiter::hit($this->throttleKey(), $this->decaySeconds());
            $audit->denied('inicio_sesion_fallido', $user, ['motivo' => $reason], $user);

            throw ValidationException::withMessages(['email' => match ($reason) {
                'cuenta_desactivada' => __('auth.inactive'),
                'institucion_no_disponible' => __('auth.institution_unavailable'),
                default => __('auth.no_role'),
            }]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::login($user); // sin "recordarme": equipos compartidos en la institución

        return $user;
    }

    /** @throws ValidationException */
    private function ensureIsNotRateLimited(AuditLogger $audit): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts())) {
            return;
        }

        event(new Lockout($this));
        $audit->denied('inicio_sesion_bloqueado', null, ['motivo' => 'demasiados_intentos']);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function maxAttempts(): int
    {
        return (int) config('seguridad_escolar.login.max_intentos', 5);
    }

    private function decaySeconds(): int
    {
        return (int) config('seguridad_escolar.login.bloqueo_segundos', 300);
    }
}
