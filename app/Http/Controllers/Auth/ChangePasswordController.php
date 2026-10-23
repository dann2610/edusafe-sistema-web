<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Cambio de contraseña por la propia persona (voluntario u obligatorio
 * cuando la cuenta tiene una contraseña temporal).
 */
class ChangePasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $request, $audit) {
            $user->forceFill([
                'password' => $request->string('password'),
                'must_change_password' => false,
            ])->save();

            // Cierra las demás sesiones abiertas de esta cuenta
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();

            $audit->success('contrasena_cambiada', $user);
        });

        $request->session()->regenerate();

        return redirect()->route('panel')->with('success', 'Contraseña actualizada correctamente.');
    }
}
