<?php

namespace App\Services;

use App\Enums\AuditResult;
use App\Models\Institution;
use App\Models\Module;
use App\Models\User;
use App\Support\ModuleCatalog;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Estado efectivo de los módulos.
 * Un módulo está activo para una institución solo si:
 *   está implementado ∧ habilitado en la plataforma ∧ autorizado para la
 *   institución ∧ habilitado por la institución ∧ sus dependencias están activas.
 * Se consulta en middleware (rutas), menú, vistas y servicios.
 */
class ModuleService
{
    /** @var array<int, array<string, bool>> */
    private array $cache = [];

    public function __construct(private readonly TenantContext $tenant) {}

    public function enabled(string $key, ?int $institutionId = null): bool
    {
        $institutionId ??= $this->tenant->id();
        if ($institutionId === null) {
            return false;
        }

        return $this->stateFor($institutionId)[$key] ?? false;
    }

    /** @return array<string, bool> */
    public function stateFor(int $institutionId): array
    {
        if (isset($this->cache[$institutionId])) {
            return $this->cache[$institutionId];
        }

        $rows = Module::query()
            ->leftJoin('institution_module as im', fn ($j) => $j->on('im.module_id', '=', 'modules.id')->where('im.institution_id', $institutionId))
            ->get(['modules.key', 'modules.is_implemented', 'modules.is_globally_enabled', 'modules.requires', 'im.is_authorized', 'im.is_enabled'])
            ->keyBy('key');

        $direct = $rows->map(fn ($r) => $r->is_implemented && $r->is_globally_enabled && $r->is_authorized && $r->is_enabled);

        $resolve = function (string $key, array $seen = []) use (&$resolve, $rows, $direct): bool {
            if (! ($direct[$key] ?? false) || in_array($key, $seen, true)) {
                return false;
            }
            foreach ($rows[$key]->requires ?? [] as $dep) {
                if (! $resolve($dep, [...$seen, $key])) {
                    return false;
                }
            }

            return true;
        };

        $state = [];
        foreach (ModuleCatalog::keys() as $key) {
            $state[$key] = isset($rows[$key]) && $resolve($key);
        }

        return $this->cache[$institutionId] = $state;
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    // ------------------------------------------------------- gestión (escritura)

    /** Plataforma: habilitar o deshabilitar un módulo para todas las instituciones. */
    public function setGlobal(Module $module, bool $enabled, User $actor): void
    {
        if ($enabled && ! $module->is_implemented) {
            throw ValidationException::withMessages(['module' => "«{$module->name}» todavía no está implementado y no puede activarse."]);
        }
        if ($enabled) {
            $this->ensureDependencies($module, fn (Module $dep) => $dep->is_globally_enabled);
        } else {
            $this->ensureNoActiveDependents($module, fn (Module $m) => $m->is_globally_enabled);
        }

        DB::transaction(function () use ($module, $enabled, $actor) {
            $module->forceFill(['is_globally_enabled' => $enabled])->save();
            app(AuditLogger::class)->mustLog($enabled ? 'modulo_habilitado_global' : 'modulo_deshabilitado_global', AuditResult::Exito, $module, ['modulo' => $module->key], $actor);
        });
        $this->flush();
    }

    /** Plataforma: autorizar o retirar un módulo para una institución. */
    public function setAuthorized(Institution $institution, Module $module, bool $authorized, User $actor): void
    {
        if ($authorized && ! $module->is_implemented) {
            throw ValidationException::withMessages(['module' => "«{$module->name}» todavía no está implementado."]);
        }
        $pivot = $this->pivot($institution, $module);
        if (! $authorized) {
            $this->ensureNoActiveDependents($module, fn (Module $m) => $this->pivot($institution, $m)['is_authorized'] ?? false, $institution);
        }

        DB::transaction(function () use ($institution, $module, $authorized, $pivot, $actor) {
            $institution->modules()->syncWithoutDetaching([$module->id => [
                'is_authorized' => $authorized,
                // Al autorizar se habilita por defecto; al retirar, se deshabilita
                'is_enabled' => $authorized ? ($pivot['is_enabled'] ?? true) : false,
                'updated_by' => $actor->id,
            ]]);
            app(AuditLogger::class)->mustLog($authorized ? 'modulo_autorizado' : 'modulo_retirado', AuditResult::Exito, $institution, ['modulo' => $module->key], $actor);
        });
        $this->flush();
    }

    /** Institución: habilitar o deshabilitar un módulo autorizado. */
    public function setEnabled(Institution $institution, Module $module, bool $enabled, User $actor): void
    {
        $pivot = $this->pivot($institution, $module);
        if ($enabled && ! ($pivot['is_authorized'] ?? false)) {
            throw ValidationException::withMessages(['module' => "«{$module->name}» no está autorizado para su institución."]);
        }
        if ($enabled) {
            $this->ensureDependencies($module, fn (Module $dep) => (bool) ($this->pivot($institution, $dep)['is_enabled'] ?? false), $institution);
        } else {
            $this->ensureNoActiveDependents($module, fn (Module $m) => (bool) ($this->pivot($institution, $m)['is_enabled'] ?? false), $institution);
        }

        DB::transaction(function () use ($institution, $module, $enabled, $actor) {
            $institution->modules()->updateExistingPivot($module->id, ['is_enabled' => $enabled, 'updated_by' => $actor->id]);
            app(AuditLogger::class)->mustLog($enabled ? 'modulo_activado' : 'modulo_desactivado', AuditResult::Exito, $institution, ['modulo' => $module->key], $actor);
        });
        $this->flush();
    }

    /** Autoriza y habilita los módulos por defecto para una institución nueva. */
    public function provision(Institution $institution, ?User $actor = null): void
    {
        foreach (Module::all() as $module) {
            $def = ModuleCatalog::MODULES[$module->key] ?? null;
            if (! $module->is_implemented || ! $def) {
                continue;
            }
            $institution->modules()->syncWithoutDetaching([$module->id => [
                'is_authorized' => true,
                'is_enabled' => $def['default'],
                'updated_by' => $actor?->id,
            ]]);
        }
        $this->flush();
    }

    /** Listado para pantallas de gestión. */
    public function overview(Institution $institution): Collection
    {
        $state = $this->stateFor($institution->id);
        $pivots = $institution->modules()->get()->keyBy('key');

        return Module::orderBy('sort_order')->get()->map(fn (Module $m) => (object) [
            'module' => $m,
            'authorized' => (bool) ($pivots[$m->key]->pivot->is_authorized ?? false),
            'enabled' => (bool) ($pivots[$m->key]->pivot->is_enabled ?? false),
            'effective' => $state[$m->key] ?? false,
            'requires' => Module::whereIn('key', $m->requires ?? [])->pluck('name')->all(),
        ]);
    }

    private function pivot(Institution $institution, Module $module): array
    {
        $row = DB::table('institution_module')->where('institution_id', $institution->id)->where('module_id', $module->id)->first();

        return $row ? (array) $row : [];
    }

    private function ensureDependencies(Module $module, callable $isOn, ?Institution $institution = null): void
    {
        $missing = Module::whereIn('key', $module->requires ?? [])->get()->reject(fn (Module $dep) => $isOn($dep));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['module' => "«{$module->name}» requiere activar primero: ".$missing->pluck('name')->implode(', ').'.']);
        }
    }

    private function ensureNoActiveDependents(Module $module, callable $isOn, ?Institution $institution = null): void
    {
        $dependents = Module::get()->filter(fn (Module $m) => in_array($module->key, $m->requires ?? [], true) && $isOn($m));
        if ($dependents->isNotEmpty()) {
            throw ValidationException::withMessages(['module' => "Primero desactive los módulos que dependen de «{$module->name}»: ".$dependents->pluck('name')->implode(', ').'.']);
        }
    }
}
