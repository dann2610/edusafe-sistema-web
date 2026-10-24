<?php

namespace App\Models;

use App\Enums\InstitutionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Institución educativa. Sus datos nunca se eliminan: se suspende o desactiva. */
class Institution extends Model
{
    protected $fillable = [
        'name', 'amie_code', 'province', 'canton', 'parish', 'address', 'phone', 'email', 'notes',
        'allow_reports_without_contact', 'close_requires_followup', 'close_requires_measures_resolved',
        'public_report_hourly_limit',
    ];

    protected function casts(): array
    {
        return [
            'status' => InstitutionStatus::class,
            'is_default' => 'boolean',
            'allow_reports_without_contact' => 'boolean',
            'close_requires_followup' => 'boolean',
            'close_requires_measures_resolved' => 'boolean',
            'public_report_hourly_limit' => 'integer',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class)->withoutGlobalScope('institution');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class)->withPivot(['is_authorized', 'is_enabled', 'config', 'updated_by'])->withTimestamps();
    }

    public function scopePubliclyAvailable(Builder $q): Builder
    {
        return $q->where('status', InstitutionStatus::Activa->value);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::orderBy('id')->first();
    }

    public function location(): string
    {
        return collect([$this->canton, $this->province])->filter()->implode(', ');
    }

    /**
     * URL pública de esta institución. La institución por defecto conserva los
     * atajos sin prefijo (/reportar); las demás usan /i/{slug}/...
     */
    public function publicRoute(string $name, array $params = []): string
    {
        return $this->is_default
            ? route('public.'.$name, $params)
            : route('inst.'.$name, ['institution' => $this] + $params);
    }
}
