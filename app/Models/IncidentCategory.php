<?php

namespace App\Models;

use App\Enums\CasePriority;
use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de incidente. Global (institution_id NULL, la administra la
 * plataforma) o local (la administra su institución). No se elimina si tiene
 * reportes: se desactiva.
 */
class IncidentCategory extends Model
{
    use BelongsToInstitution;

    /** Incluye registros globales (institution_id NULL) además de los de la institución. */
    protected static bool $allowsGlobal = true;

    protected $fillable = [
        'code', 'name', 'description', 'default_priority', 'available_in_public_form', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_priority' => CasePriority::class,
            'available_in_public_form' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(IncidentCategoryField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeFields(): HasMany
    {
        return $this->fields()->where('is_active', true);
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForPublic(Builder $query): Builder
    {
        return $query->active()->where('available_in_public_form', true)->ordered();
    }
}
