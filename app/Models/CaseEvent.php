<?php

namespace App\Models;

use App\Enums\CaseEventType;
use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Cronología del caso (inmutable).
 * summary es un texto breve de lo ocurrido ("Se asignó a ..."), nunca la descripción de la denuncia.
 */
class CaseEvent extends Model
{
    use Immutable;

    public const UPDATED_AT = null;

    protected $fillable = ['incident_id', 'event_type', 'user_id', 'summary', 'subject_type', 'subject_id', 'metadata'];

    protected function casts(): array
    {
        return [
            'event_type' => CaseEventType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
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

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
