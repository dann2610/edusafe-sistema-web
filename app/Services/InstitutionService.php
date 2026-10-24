<?php

namespace App\Services;

use App\Enums\AuditResult;
use App\Enums\InstitutionStatus;
use App\Enums\RoleSlug;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Alta y ciclo de vida de instituciones (superadministrador). */
class InstitutionService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ModuleService $modules,
        private readonly UserAccountService $accounts,
    ) {}

    /**
     * Registra la institución, habilita sus módulos por defecto y crea su
     * administrador inicial con contraseña temporal.
     *
     * @return array{0: Institution, 1: User, 2: string}
     */
    public function create(array $data, User $actor): array
    {
        return DB::transaction(function () use ($data, $actor) {
            $institution = new Institution($data);
            $institution->code_prefix = strtoupper($data['code_prefix']);
            $institution->slug = $this->uniqueSlug($data['code_prefix']);
            $institution->status = InstitutionStatus::from($data['status']);
            $institution->activated_at = $institution->status === InstitutionStatus::Activa ? now() : null;
            $institution->is_default = ! Institution::where('is_default', true)->exists();
            $institution->save();

            $this->modules->provision($institution, $actor);

            [$admin, $password] = $this->accounts->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'position' => 'Administrador institucional',
                'role_id' => Role::where('slug', RoleSlug::Administrador->value)->value('id'),
            ], $institution->id);

            $this->audit->mustLog('institucion_creada', AuditResult::Exito, $institution, ['siglas' => $institution->code_prefix, 'estado' => $institution->status->value], $actor);

            return [$institution, $admin, $password];
        });
    }

    public function update(Institution $institution, array $data, User $actor): void
    {
        DB::transaction(function () use ($institution, $data, $actor) {
            $institution->fill($data);
            $changes = array_keys($institution->getDirty());
            $institution->save();
            if ($changes) {
                $this->audit->mustLog('institucion_actualizada', AuditResult::Exito, $institution, ['campos' => $changes], $actor);
            }
        });
    }

    /** Cambia el estado. Suspender o desactivar no elimina datos; cierra las sesiones del personal. */
    public function changeStatus(Institution $institution, InstitutionStatus $status, string $reason, User $actor): void
    {
        DB::transaction(function () use ($institution, $status, $reason, $actor) {
            $previous = $institution->status;
            $institution->status = $status;
            if ($status === InstitutionStatus::Activa && ! $institution->activated_at) {
                $institution->activated_at = now();
            }
            $institution->suspended_at = in_array($status, [InstitutionStatus::Suspendida, InstitutionStatus::Inactiva], true) ? now() : null;
            $institution->save();

            if (! $status->allowsStaffLogin()) {
                DB::table('sessions')->whereIn('user_id', $institution->users()->pluck('id'))->delete();
            }

            $this->audit->mustLog('institucion_estado_cambiado', AuditResult::Exito, $institution, [
                'de' => $previous->value, 'a' => $status->value, 'motivo' => $reason,
            ], $actor);
        });
    }

    private function uniqueSlug(string $prefix): string
    {
        $base = Str::slug($prefix) ?: 'institucion';
        $slug = $base;
        $i = 2;
        while (Institution::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
