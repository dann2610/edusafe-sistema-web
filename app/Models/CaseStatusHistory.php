<?php

namespace App\Models;

use App\Enums\CaseStatus;
use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial de estados (inmutable). */
class CaseStatusHistory extends Model
{
    use Immutable;

    protected $table = 'case_status_history';

    public const UPDATED_AT = null;

    protected $fillable = ['incident_id', 'from_status', 'to_status', 'changed_by', 'justification'];

    protected function casts(): array
    {
        return [
            'from_status' => CaseStatus::class,
            'to_status' => CaseStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
