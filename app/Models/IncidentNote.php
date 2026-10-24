<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Información adicional aportada por quien registró el caso (inmutable). */
class IncidentNote extends Model
{
    use Immutable;

    public const UPDATED_AT = null;

    protected $fillable = ['body'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
