<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AcademicPeriod extends Model
{
    use BelongsToInstitution;

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

    /**
     * Periodo vigente. Sin argumento usa la institución del contexto actual;
     * con argumento consulta explícitamente esa institución (consola, servicios).
     */
    public static function current(?int $institutionId = null): ?self
    {
        $query = $institutionId === null ? static::query() : static::withoutInstitutionScope()->where('institution_id', $institutionId);

        return $query->where('is_current', true)->first();
    }

    /** Marca este periodo como vigente de su institución (y desmarca el anterior). */
    public function markAsCurrent(): void
    {
        DB::transaction(function () {
            static::withoutInstitutionScope()->where('institution_id', $this->institution_id)
                ->where('is_current', true)->whereKeyNot($this->getKey())->update(['is_current' => false]);
            $this->forceFill(['is_current' => true])->save();
        });
    }
}
