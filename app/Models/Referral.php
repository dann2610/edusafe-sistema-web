<?php

namespace App\Models;

use App\Enums\ReferralChannel;
use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Derivación a una entidad externa. Solo se documenta la actuación realizada
 * por los canales oficiales; no existe integración automática.
 * shared_summary delimita la información visible para la entidad destinataria.
 */
class Referral extends Model
{
    protected $fillable = [
        'external_entity_id', 'referred_on', 'reason', 'channel', 'reference_number',
        'shared_summary', 'observations', 'management_status',
    ];

    protected function casts(): array
    {
        return [
            'referred_on' => 'date',
            'channel' => ReferralChannel::class,
            'management_status' => ReferralStatus::class,
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function externalEntity(): BelongsTo
    {
        return $this->belongsTo(ExternalEntity::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
