<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = ['name', 'description', 'config'];

    protected function casts(): array
    {
        return [
            'is_implemented' => 'boolean',
            'is_globally_enabled' => 'boolean',
            'requires' => 'array',
            'permissions' => 'array',
            'config' => 'array',
        ];
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)->withPivot(['is_authorized', 'is_enabled', 'config', 'updated_by'])->withTimestamps();
    }
}
