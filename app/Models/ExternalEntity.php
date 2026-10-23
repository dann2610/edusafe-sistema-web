<?php

namespace App\Models;

use App\Enums\ExternalEntityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalEntity extends Model
{
    protected $fillable = [
        'name', 'type', 'institutional_phone', 'institutional_email', 'address', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExternalEntityType::class,
            'is_active' => 'boolean',
        ];
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
