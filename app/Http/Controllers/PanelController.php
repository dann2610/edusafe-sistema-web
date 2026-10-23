<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('role.permissions', 'externalEntity');

        $functions = collect(PermissionCatalog::MATRIX)
            ->filter(fn ($def, $slug) => $user->hasPermission($slug))
            ->map(fn ($def) => $def[0])
            ->values();

        $stats = null;
        if ($user->can('usuarios.gestionar')) {
            $stats = [
                'activos' => User::active()->count(),
                'inactivos' => User::where('is_active', false)->count(),
                'pendientes' => User::active()->where('must_change_password', true)->count(),
            ];
        }

        return view('panel.index', compact('user', 'functions', 'stats'));
    }
}
