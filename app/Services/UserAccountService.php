<?php

namespace App\Services;

use App\Enums\AuditResult;
use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reglas de negocio de cuentas de usuario.
 * Toda operación es transaccional y queda registrada en la bitácora.
 */
class UserAccountService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Crea la cuenta con una contraseña temporal aleatoria que debe cambiarse
     * en el primer inicio de sesión. Devuelve [usuario, contraseña temporal].
     *
     * @return array{0: User, 1: string}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $temporary = $this->temporaryPassword();
            $role = Role::findOrFail($data['role_id']);

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'position' => $data['position'] ?? null,
                'password' => $temporary,
            ]);
            $user->role_id = $role->id;
            $user->external_entity_id = $role->hasSlug(RoleSlug::Policia) ? ($data['external_entity_id'] ?? null) : null;
            $user->is_active = true;
            $user->must_change_password = true;
            $user->email_verified_at = now();
            $user->save();

            $this->audit->mustLog('usuario_creado', AuditResult::Exito, $user, ['rol' => $role->slug]);

            return [$user, $temporary];
        });
    }

    /** Actualiza datos y, si corresponde y está permitido, el rol. */
    public function update(User $user, array $data, bool $canChangeRole): User
    {
        return DB::transaction(function () use ($user, $data, $canChangeRole) {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'position' => $data['position'] ?? null,
            ]);

            $roleChanged = false;
            if ($canChangeRole && isset($data['role_id']) && (int) $data['role_id'] !== (int) $user->role_id) {
                $previous = $user->role?->slug;
                $user->role_id = (int) $data['role_id'];
                $user->setRelation('role', Role::findOrFail($user->role_id));
                $roleChanged = true;
            }

            $user->external_entity_id = $user->hasRole(RoleSlug::Policia) ? ($data['external_entity_id'] ?? null) : null;

            $changes = array_keys($user->getDirty());
            $user->save();

            if ($roleChanged) {
                // Un cambio de rol invalida las sesiones abiertas para aplicar los nuevos permisos
                $this->closeSessions($user);
                $this->audit->mustLog('usuario_rol_cambiado', AuditResult::Exito, $user, [
                    'rol_anterior' => $previous, 'rol_nuevo' => $user->role->slug,
                ]);
            }

            if ($changes !== []) {
                $this->audit->mustLog('usuario_actualizado', AuditResult::Exito, $user, ['campos' => $changes]);
            }

            return $user;
        });
    }

    public function deactivate(User $user, string $reason): void
    {
        DB::transaction(function () use ($user, $reason) {
            $user->is_active = false;
            $user->deactivated_at = now();
            $user->save();
            $this->closeSessions($user);
            $this->audit->mustLog('usuario_desactivado', AuditResult::Exito, $user, ['motivo' => $reason]);
        });
    }

    public function activate(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->is_active = true;
            $user->deactivated_at = null;
            $user->save();
            $this->audit->mustLog('usuario_activado', AuditResult::Exito, $user);
        });
    }

    /** Genera una nueva contraseña temporal y obliga a cambiarla. */
    public function resetTemporaryPassword(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $temporary = $this->temporaryPassword();
            $user->forceFill(['password' => $temporary, 'must_change_password' => true])->save();
            $this->closeSessions($user);
            $this->audit->mustLog('usuario_contrasena_temporal', AuditResult::Exito, $user);

            return $temporary;
        });
    }

    private function closeSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /** 16 caracteres que siempre cumplen la política (mayúscula, minúscula, número y símbolo). */
    private function temporaryPassword(): string
    {
        do {
            $password = Str::password(16);
        } while (! preg_match('/[A-Z]/', $password) || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/\d/', $password) || ! preg_match('/[^A-Za-z0-9]/', $password));

        return $password;
    }
}
