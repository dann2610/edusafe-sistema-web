<?php

namespace App\Models;

use App\Enums\MeasureKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasureType extends Model
{
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
