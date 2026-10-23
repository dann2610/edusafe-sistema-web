<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['slug', 'name', 'description'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasSlug(RoleSlug|string $slug): bool
    {
        return $this->slug === ($slug instanceof RoleSlug ? $slug->value : $slug);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->relationLoaded('permissions')
            ? $this->permissions->contains('slug', $permission)
            : $this->permissions()->where('slug', $permission)->exists();
    }
}
