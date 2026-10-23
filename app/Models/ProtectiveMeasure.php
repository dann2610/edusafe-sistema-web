<?php

namespace App\Models;

use App\Enums\ComplianceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Medida formativa, preventiva o de protección.
 * El sistema documenta la decisión institucional; no decide sanciones.
 */
class ProtectiveMeasure extends Model
{
    protected $fillable = [
        'measure_type_id', 'description', 'responsible_user_id', 'starts_on',
        'review_on', 'ends_on', 'compliance_status', 'observations',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'review_on' => 'date',
            'ends_on' => 'date',
            'compliance_status' => ComplianceStatus::class,
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MeasureType::class, 'measure_type_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
