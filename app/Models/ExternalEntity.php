<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use App\Enums\ExternalEntityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalEntity extends Model
{
    use BelongsToInstitution;

    /** Incluye registros globales (institution_id NULL) además de los de la institución. */
    protected static bool $allowsGlobal = true;

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
