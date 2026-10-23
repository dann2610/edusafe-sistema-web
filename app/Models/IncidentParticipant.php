<?php

namespace App\Models;

use App\Enums\ParticipantRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentParticipant extends Model
{
    protected $fillable = ['student_id', 'non_student_description', 'role', 'notes'];

    protected function casts(): array
    {
        return ['role' => ParticipantRole::class];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
