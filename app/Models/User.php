<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * role_id, external_entity_id e is_active NO son asignables en masa:
     * se modifican solo desde servicios con autorización explícita.
     */
    protected $fillable = [
        'name',
        'email',
        'position',
        'password',
    ];

    /** Valores por defecto coherentes con la migración. */
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function externalEntity(): BelongsTo
    {
        return $this->belongsTo(ExternalEntity::class);
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reporter_user_id');
    }

    public function caseAssignments(): HasMany
    {
        return $this->hasMany(CaseAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->caseAssignments()->whereNull('unassigned_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole(Builder $query, RoleSlug $role): Builder
    {
        return $query->whereHas('role', fn (Builder $q) => $q->where('slug', $role->value));
    }

    public function hasRole(RoleSlug ...$roles): bool
    {
        if (! $this->role) {
            return false;
        }
        foreach ($roles as $role) {
            if ($this->role->hasSlug($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->is_active && $this->role !== null && $this->role->hasPermission($permission);
    }

    public function roleSlug(): ?RoleSlug
    {
        return $this->role ? RoleSlug::tryFrom($this->role->slug) : null;
    }
}
