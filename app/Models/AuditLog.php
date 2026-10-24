<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use App\Enums\AuditResult;
use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora de auditoría (inmutable). Se escribe solo mediante App\Services\AuditLogger. */
class AuditLog extends Model
{
    use BelongsToInstitution;

    use Immutable;

    public const UPDATED_AT = null;

    protected $fillable = ['institution_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'result', 'ip_address', 'metadata'];

    protected function casts(): array
    {
        return [
            'result' => AuditResult::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
