<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\ReporterRelationship;
use App\Enums\ReportSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Incidente / caso.
 * status, priority, code y closed_at NO son asignables en masa: solo los
 * modifican los servicios de negocio (registro, transiciones, cierre).
 */
class Incident extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'incident_category_id',
        'academic_period_id',
        'course_id',
        'occurred_at',
        'occurred_at_is_approximate',
        'location',
        'description',
        'immediate_risk',
        'reporter_relationship',
        'reporter_contact_name',
        'reporter_contact',
        'privacy_notice_accepted',
        'occurred_time_known',
        'is_ongoing',
        'authority_informed',
        'authority_informed_detail',
        'reporter_mode',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'occurred_at_is_approximate' => 'boolean',
            'occurred_time_known' => 'boolean',
            'extra_data' => 'array',
            'immediate_risk' => 'boolean',
            'privacy_notice_accepted' => 'boolean',
            'closed_at' => 'datetime',
            'status' => CaseStatus::class,
            'priority' => CasePriority::class,
            'source' => ReportSource::class,
            'reporter_relationship' => ReporterRelationship::class,
        ];
    }

    protected $hidden = ['public_access_hash'];

    /**
     * Genera un código único: SIGLAS-AÑO-000001 usando la secuencia de PostgreSQL
     * (atómica y segura frente a concurrencia). Las siglas son las de la institución.
     */
    public static function generateCode(?Institution $institution = null): string
    {
        $next = DB::selectOne("SELECT nextval('incident_code_seq') AS n")->n;
        $prefix = $institution?->code_prefix ?? config('seguridad_escolar.institucion.siglas', 'UE');

        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $next);
    }

    /** Opciones de sí / no / no sabe usadas en el formulario. */
    public const YES_NO_UNKNOWN = ['si' => 'Sí', 'no' => 'No', 'no_sabe' => 'No sé'];

    public static function yesNoLabel(?string $value): string
    {
        return $value ? (self::YES_NO_UNKNOWN[$value] ?? $value) : 'No indicado';
    }

    public function occurredLabel(): string
    {
        if (! $this->occurred_at) {
            return 'Fecha desconocida';
        }

        return $this->occurred_time_known ? $this->occurred_at->format('d/m/Y H:i') : $this->occurred_at->format('d/m/Y').' (hora desconocida)';
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // ---------------------------------------------------------------- relaciones

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'incident_category_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(IncidentParticipant::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CaseAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('unassigned_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CaseStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class)->orderBy('performed_at');
    }

    public function measures(): HasMany
    {
        return $this->hasMany(ProtectiveMeasure::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(IncidentNote::class)->orderBy('created_at');
    }

    // -------------------------------------------------------------------- scopes

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', CaseStatus::Cerrado->value);
    }

    public function scopeWithStatus(Builder $query, CaseStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /** Orden de bandeja: urgentes primero, luego más recientes. */
    public function scopeInboxOrder(Builder $query): Builder
    {
        return $query->orderByRaw("CASE priority WHEN 'urgente' THEN 0 WHEN 'alta' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at');
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->activeAssignments()->where('user_id', $user->id)->exists();
    }
}
