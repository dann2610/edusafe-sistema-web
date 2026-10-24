<?php

namespace App\Models\Concerns;

use App\Models\Institution;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aislamiento por institución en la capa de acceso a datos.
 * - Toda consulta Eloquent se limita a la institución del contexto actual.
 *   Por eso una URL con el identificador de otra institución devuelve 404.
 * - Los modelos con $allowsGlobal = true (catálogos) incluyen además los
 *   registros globales (institution_id NULL).
 * - Al crear, institution_id se completa con la institución del contexto.
 *
 * Para consultas de plataforma (superadministrador) use withoutInstitutionScope().
 */
trait BelongsToInstitution
{
    /** Propiedad (no columna): permite crear explícitamente un registro global. */
    public bool $creatingGlobal = false;

    public static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope('institution', function (Builder $query) {
            $id = app(TenantContext::class)->id();
            if ($id === null) {
                return;
            }
            $column = $query->getModel()->qualifyColumn('institution_id');
            if (static::allowsGlobalRecords()) {
                $query->where(fn (Builder $q) => $q->where($column, $id)->orWhereNull($column));
            } else {
                $query->where($column, $id);
            }
        });

        static::creating(function ($model) {
            if ($model->institution_id === null && ! $model->creatingGlobal) {
                $model->institution_id = app(TenantContext::class)->id();
            }
        });
    }

    public static function allowsGlobalRecords(): bool
    {
        return property_exists(static::class, 'allowsGlobal') && static::$allowsGlobal;
    }

    public static function withoutInstitutionScope(): Builder
    {
        return static::withoutGlobalScope('institution');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function isGlobal(): bool
    {
        return $this->institution_id === null;
    }

    /** ¿Puede este registro ser modificado desde la institución indicada? */
    public function isOwnedBy(?int $institutionId): bool
    {
        return $institutionId !== null && $this->institution_id === $institutionId;
    }
}
