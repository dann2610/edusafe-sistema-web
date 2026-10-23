<?php

namespace App\Models;

use App\Enums\AssignmentRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAssignment extends Model
{
    protected $fillable = ['user_id', 'assignment_role', 'notes'];

    protected function casts(): array
    {
        return [
            'assignment_role' => AssignmentRole::class,
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function unassignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unassigned_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }

    public function isActive(): bool
    {
        return $this->unassigned_at === null;
    }
}
