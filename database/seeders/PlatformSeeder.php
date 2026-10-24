<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Module;
use App\Services\ModuleService;
use App\Support\ModuleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Datos de plataforma (idempotente, también para producción):
 * - Institución por defecto a partir de config/seguridad_escolar.php, si no existe ninguna.
 * - Catálogo de módulos sincronizado con App\Support\ModuleCatalog sin alterar
 *   las decisiones ya tomadas (habilitado, autorizado).
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            if (! Institution::exists()) {
                $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) config('seguridad_escolar.institucion.siglas', 'UE'))) ?: 'UE';
                $institution = new Institution(['name' => config('seguridad_escolar.institucion.nombre', 'Unidad Educativa')]);
                $institution->forceFill(['slug' => Str::slug($prefix), 'code_prefix' => $prefix, 'status' => 'activa', 'is_default' => true, 'activated_at' => now()])->save();
            }

            $order = 0;
            foreach (ModuleCatalog::MODULES as $key => $def) {
                $module = Module::firstOrNew(['key' => $key]);
                $isNew = ! $module->exists;
                $module->forceFill([
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'is_implemented' => $def['implemented'],
                    'requires' => $def['requires'],
                    'permissions' => $def['permissions'],
                    'sort_order' => $order += 10,
                    // Un módulo no implementado nunca queda habilitado
                    'is_globally_enabled' => $def['implemented'] && ($isNew ? true : $module->is_globally_enabled),
                ])->save();
            }

            // Instituciones sin módulos asignados reciben los valores por defecto
            foreach (Institution::doesntHave('modules')->get() as $institution) {
                app(ModuleService::class)->provision($institution);
            }
        });
    }
}
