<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles y matriz de permisos (principio de mínimo privilegio).
 * Idempotente: puede ejecutarse varias veces sin duplicar registros.
 * El administrador técnico NO recibe permisos sobre expedientes de casos.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** slug => [nombre, módulo, roles] */
    public const MATRIX = [
        // Administración técnica
        'usuarios.gestionar' => ['Gestionar cuentas y roles', 'usuarios', ['administrador']],
        'catalogos.gestionar' => ['Gestionar catálogos institucionales', 'catalogos', ['administrador']],
        'configuracion.gestionar' => ['Gestionar configuración técnica y código QR', 'configuracion', ['administrador']],
        'auditoria.consultar' => ['Consultar bitácora de auditoría', 'auditoria', ['administrador', 'autoridad']],

        // Registro de incidentes
        'incidentes.registrar' => ['Registrar incidentes', 'incidentes', ['docente', 'dece', 'autoridad']],
        'incidentes.ver_propios' => ['Consultar los reportes propios', 'incidentes', ['docente', 'dece', 'autoridad']],
        'adjuntos.subir' => ['Adjuntar evidencias permitidas', 'incidentes', ['docente', 'dece']],
        'estudiantes.buscar' => ['Buscar estudiantes para vincularlos a un caso', 'incidentes', ['docente', 'dece']],

        // Gestión de casos (DECE)
        'casos.bandeja' => ['Acceder a la bandeja de casos', 'casos', ['dece']],
        'casos.ver_todos' => ['Consultar el expediente de cualquier caso', 'casos', ['dece']],
        'casos.gestionar' => ['Revisar, priorizar y cambiar estados', 'casos', ['dece']],
        'casos.asignar' => ['Asignar responsables', 'casos', ['dece']],
        'seguimientos.registrar' => ['Registrar seguimientos (DECE o responsable asignado)', 'casos', ['dece', 'docente']],
        'medidas.gestionar' => ['Registrar y actualizar medidas', 'casos', ['dece']],
        'derivaciones.gestionar' => ['Registrar y actualizar derivaciones', 'casos', ['dece']],

        // Entidad externa
        'derivaciones.ver_entidad' => ['Consultar casos derivados a su entidad', 'derivaciones', ['policia']],

        // Indicadores y reportes
        'dashboard.ver' => ['Consultar el dashboard de indicadores agregados', 'reportes', ['dece', 'autoridad']],
        'reportes.generar' => ['Generar reportes agregados PDF/Excel', 'reportes', ['dece', 'autoridad']],
        'reportes.exportar_identificable' => ['Exportar información identificable', 'reportes', ['dece']],
    ];

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
            foreach (self::MATRIX as $slug => [$name, $module, $roleSlugs]) {
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
