<?php

namespace App\Policies;

use App\Models\User;

/**
 * Reglas de gestión de cuentas.
 * - Solo quien tiene "usuarios.gestionar" administra cuentas.
 * - Nadie puede desactivarse, cambiarse el rol ni regenerarse la contraseña a sí mismo desde este módulo.
 * - El último administrador técnico activo no puede desactivarse ni perder su rol.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('usuarios.gestionar');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasPermission('usuarios.gestionar');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('usuarios.gestionar');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->hasPermission('usuarios.gestionar');
    }

    public function changeRole(User $actor, User $target): bool
    {
        return $actor->hasPermission('usuarios.gestionar')
            && ! $actor->is($target)
            && ! $target->isLastActiveAdministrator();
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->hasPermission('usuarios.gestionar')
            && ! $actor->is($target)
            && $target->is_active
            && ! $target->isLastActiveAdministrator();
    }

    public function activate(User $actor, User $target): bool
    {
        return $actor->hasPermission('usuarios.gestionar') && ! $target->is_active;
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->hasPermission('usuarios.gestionar')
            && ! $actor->is($target)
            && $target->is_active;
    }
}
