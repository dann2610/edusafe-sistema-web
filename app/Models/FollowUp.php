<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

/**
 * Seguimiento / actuación. No se edita ni elimina: las correcciones se
 * registran como un nuevo seguimiento que referencia al original.
 */
class FollowUp extends Model
{
    protected $fillable = [
        'follow_up_type_id', 'performed_at', 'description', 'observations',
        'next_action', 'next_follow_up_on', 'corrects_follow_up_id', 'correction_reason',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'next_follow_up_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los seguimientos no se editan: registre una corrección.'));
        static::deleting(fn () => throw new LogicException('Los seguimientos no se eliminan.'));
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(FollowUpType::class, 'follow_up_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function corrects(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_follow_up_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'corrects_follow_up_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
