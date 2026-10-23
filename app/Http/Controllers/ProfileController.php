<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perfil propio (solo lectura). Nombre, correo y rol los modifica el
 * administrador técnico; la persona solo puede cambiar su contraseña.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()->loadMissing('role', 'externalEntity')]);
    }
}
