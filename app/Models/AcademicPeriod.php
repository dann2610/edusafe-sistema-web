<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AcademicPeriod extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_active'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /** Marca este periodo como vigente (y desmarca el anterior) en una transacción. */
    public function markAsCurrent(): void
    {
        DB::transaction(function () {
            static::where('is_current', true)->whereKeyNot($this->getKey())->update(['is_current' => false]);
            $this->forceFill(['is_current' => true])->save();
        });
    }
}
