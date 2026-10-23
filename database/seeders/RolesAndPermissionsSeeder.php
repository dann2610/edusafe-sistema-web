<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles y matriz de permisos (principio de mínimo privilegio).
 * La matriz vive en App\Support\PermissionCatalog.
 * Idempotente: puede ejecutarse varias veces sin duplicar registros.
 * El administrador técnico NO recibe permisos sobre expedientes de casos.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = [];
            foreach (RoleSlug::cases() as $slug) {
                $roles[$slug->value] = Role::updateOrCreate(
                    ['slug' => $slug->value],
                    ['name' => $slug->label(), 'description' => $slug->description()],
                );
            }

            $byRole = array_fill_keys(array_keys($roles), []);
            foreach (PermissionCatalog::MATRIX as $slug => [$name, $module, $roleSlugs]) {
                $permission = Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => $module]);
                foreach ($roleSlugs as $r) {
                    $byRole[$r][] = $permission->id;
                }
            }

            foreach ($roles as $slug => $role) {
                $role->permissions()->sync($byRole[$slug]);
            }
        });
    }
}
