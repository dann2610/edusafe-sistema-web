<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estudiante. Datos mínimos: código institucional y nombres.
 * Nunca se exponen en el dashboard ni en reportes agregados.
 */
class Student extends Model
{
    use BelongsToInstitution;

    use HasFactory;

    protected $fillable = ['institutional_code', 'first_names', 'last_names', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')->withTimestamps();
    }

    public function participations(): HasMany
    {
        return $this->hasMany(IncidentParticipant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->last_names} {$this->first_names}"));
    }
}
