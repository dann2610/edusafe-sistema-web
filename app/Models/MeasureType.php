<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use App\Enums\MeasureKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasureType extends Model
{
    use BelongsToInstitution;

    /** Incluye registros globales (institution_id NULL) además de los de la institución. */
    protected static bool $allowsGlobal = true;

    protected $fillable = ['name', 'kind', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'kind' => MeasureKind::class,
            'is_active' => 'boolean',
        ];
    }

    public function measures(): HasMany
    {
        return $this->hasMany(ProtectiveMeasure::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
