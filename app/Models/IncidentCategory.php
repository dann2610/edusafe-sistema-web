<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentCategory extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'suggests_high_priority', 'available_in_public_form', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'suggests_high_priority' => 'boolean',
            'available_in_public_form' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
