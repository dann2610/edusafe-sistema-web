<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Recuperación de contraseña por correo.
 * La respuesta es siempre la misma exista o no el correo (evita enumerar cuentas).
 * Solo las cuentas activas reciben el enlace.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($request->string('email')));

        $status = Password::sendResetLink(['email' => $email, 'is_active' => true]);

        if ($status === Password::RESET_LINK_SENT) {
            $audit->success('recuperacion_solicitada', User::findByEmail($email), [], User::findByEmail($email));
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __('passwords.throttled')]);
        }

        // Mismo mensaje para correos inexistentes o cuentas inactivas
        return back()->with('status', __('passwords.sent'));
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $credentials = [
            'email' => mb_strtolower(trim($request->string('email'))),
            'password' => $request->string('password'),
            'password_confirmation' => $request->string('password_confirmation'),
            'token' => $request->string('token'),
            'is_active' => true,
        ];

        $status = Password::reset($credentials, function (User $user, string $password) use ($audit) {
            DB::transaction(function () use ($user, $password, $audit) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                // Cierra cualquier otra sesión abierta con la contraseña anterior
                DB::table('sessions')->where('user_id', $user->id)->delete();

                $audit->success('contrasena_restablecida', $user, [], $user);
            });

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('passwords.token')]);
        }

        return redirect()->route('login')->with('status', __('passwords.reset'));
    }
}
