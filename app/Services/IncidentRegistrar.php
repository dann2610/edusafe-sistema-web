<?php

namespace App\Services;

use App\Enums\AssignmentRole;
use App\Enums\AuditResult;
use App\Enums\CaseEventType;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\ReportSource;
use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\CaseEvent;
use App\Models\CaseStatusHistory;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Institution;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\IncidentReportedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Registro de incidentes (interno y público).
 * - El caso queda siempre en la institución de quien registra o de la página pública.
 * - La prioridad inicial refleja lo comunicado: riesgo inmediato → urgente; si no,
 *   la prioridad configurada en la categoría. El sistema no evalúa veracidad.
 * - Los campos adicionales se guardan con su etiqueta (copia), para que los
 *   cambios de configuración no alteren reportes antiguos.
 * - Si la categoría local define un responsable inicial, se asigna automáticamente
 *   (el caso sigue en «Registrado» hasta que el DECE lo revise).
 */
class IncidentRegistrar
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AttachmentStorage $storage,
    ) {}

    /**
     * @param  array<int, array{student_id?: ?int, non_student_description?: ?string, role: string, notes?: ?string}>  $participants
     * @param  array<int, UploadedFile>  $files
     */
    public function registerInternal(User $reporter, array $data, array $participants = [], array $files = []): Incident
    {
        return $this->register(ReportSource::Interno, $reporter->institution, $data, $reporter, $participants, $files);
    }

    /**
     * @return array{0: Incident, 1: ?string} caso y clave de consulta (si el módulo está activo)
     */
    public function registerPublic(Institution $institution, array $data, array $participants = [], array $files = [], bool $withAccessKey = false): array
    {
        $key = $withAccessKey ? strtoupper(Str::random(4).'-'.Str::random(4)) : null;
        $incident = $this->register(ReportSource::Publico, $institution, $data, null, $participants, $files, $key);

        return [$incident, $key];
    }

    private function register(ReportSource $source, Institution $institution, array $data, ?User $reporter, array $participants = [], array $files = [], ?string $accessKey = null): Incident
    {
        [$incident, $autoAssignee] = DB::transaction(function () use ($source, $institution, $data, $reporter, $participants, $files, $accessKey) {
            $category = IncidentCategory::withoutInstitutionScope()
                ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $institution->id))
                ->findOrFail($data['incident_category_id']);
            $public = $source === ReportSource::Publico;

            $incident = new Incident([
                'incident_category_id' => $category->id,
                'academic_period_id' => AcademicPeriod::current($institution->id)?->id,
                'course_id' => $data['course_id'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? null,
                'occurred_at_is_approximate' => true,
                'occurred_time_known' => (bool) ($data['occurred_time_known'] ?? true),
                'location' => $data['location'] ?? null,
                'description' => $data['description'],
                'immediate_risk' => (bool) ($data['immediate_risk'] ?? false),
                'is_ongoing' => $data['is_ongoing'] ?? null,
                'authority_informed' => $data['authority_informed'] ?? null,
                'authority_informed_detail' => $data['authority_informed_detail'] ?? null,
                'reporter_relationship' => $public ? ($data['reporter_relationship'] ?? null) : null,
                'reporter_mode' => $public ? ($data['reporter_mode'] ?? null) : null,
                'reporter_contact_name' => $public ? ($data['reporter_contact_name'] ?? null) : null,
                'reporter_contact' => $public ? ($data['reporter_contact'] ?? null) : null,
                'privacy_notice_accepted' => $public,
            ]);
            $incident->forceFill([
                'institution_id' => $institution->id,
                'code' => Incident::generateCode($institution),
                'status' => CaseStatus::Registrado,
                'priority' => $incident->immediate_risk ? CasePriority::Urgente : ($category->default_priority ?? CasePriority::Normal),
                'source' => $source,
                'reporter_user_id' => $reporter?->id,
                'extra_data' => $this->extraData($category, $data['extra'] ?? [], $public) ?: null,
                'public_access_hash' => $accessKey ? Hash::make($accessKey) : null,
            ])->save();

            CaseStatusHistory::create([
                'incident_id' => $incident->id,
                'from_status' => null,
                'to_status' => CaseStatus::Registrado->value,
                'changed_by' => $reporter?->id,
                'justification' => $public ? 'Reporte recibido por el formulario público.' : null,
            ]);
            CaseEvent::create([
                'incident_id' => $incident->id,
                'event_type' => CaseEventType::Creado->value,
                'user_id' => $reporter?->id,
                'summary' => $public ? 'Reporte recibido por el formulario público' : 'Incidente registrado por '.$reporter->name,
                'metadata' => ['prioridad' => $incident->priority->value, 'riesgo_inmediato' => $incident->immediate_risk],
            ]);

            foreach ($participants as $p) {
                $participant = $incident->participants()->make([
                    'student_id' => $p['student_id'] ?? null,
                    'non_student_description' => $p['non_student_description'] ?? null,
                    'role' => $p['role'],
                    'notes' => $p['notes'] ?? null,
                ]);
                $participant->registered_by = $reporter?->id;
                $participant->save();
            }
            if ($participants) {
                CaseEvent::create([
                    'incident_id' => $incident->id, 'event_type' => CaseEventType::ParticipanteAgregado->value,
                    'user_id' => $reporter?->id, 'summary' => count($participants).' persona(s) involucrada(s) registradas',
                ]);
            }

            foreach ($files as $file) {
                $this->storage->store($file, $incident, $incident, $reporter);
            }
            if ($files) {
                CaseEvent::create([
                    'incident_id' => $incident->id, 'event_type' => CaseEventType::Adjunto->value,
                    'user_id' => $reporter?->id, 'summary' => count($files).' evidencia(s) adjunta(s)',
                ]);
            }

            // Responsable inicial configurado en una categoría local de la misma institución
            $autoAssignee = null;
            $candidate = $category->institution_id === $institution->id ? $category->defaultAssignee : null;
            if ($candidate && $candidate->is_active && $candidate->institution_id === $institution->id && $candidate->hasPermission('seguimientos.registrar')) {
                $assignment = $incident->assignments()->make(['user_id' => $candidate->id, 'assignment_role' => AssignmentRole::Principal->value, 'notes' => 'Responsable inicial definido en la categoría.']);
                $assignment->assigned_by = $reporter?->id ?? $candidate->id;
                $assignment->assigned_at = now();
                $assignment->save();
                CaseEvent::create([
                    'incident_id' => $incident->id, 'event_type' => CaseEventType::Asignado->value, 'user_id' => $reporter?->id,
                    'summary' => "Asignación automática a {$candidate->name} (responsable inicial de la categoría)",
                    'subject_type' => $assignment->getMorphClass(), 'subject_id' => $assignment->id,
                ]);
                $autoAssignee = $candidate;
            }

            $this->audit->mustLog(
                $public ? 'reporte_publico_recibido' : 'incidente_registrado',
                AuditResult::Exito, $incident, ['codigo' => $incident->code], $reporter,
            );

            return [$incident, $autoAssignee];
        });

        // Notificaciones internas, fuera de la transacción y solo a la institución del caso
        $dece = User::active()->ofInstitution($institution->id)->withRole(RoleSlug::Dece)->get();
        Notification::send($dece, new IncidentReportedNotification($incident));
        if ($autoAssignee && ! $dece->contains('id', $autoAssignee->id)) {
            $autoAssignee->notify(new CaseAssignedNotification($incident));
        }

        return $incident;
    }

    /**
     * Copia validada de los campos adicionales: [{key, label, type, value}].
     * Solo se guardan los campos activos de la categoría (y visibles al público si aplica).
     */
    private function extraData(IncidentCategory $category, array $values, bool $public): array
    {
        $out = [];
        foreach ($category->activeFields()->get() as $field) {
            if ($public && ! $field->show_in_public_form) {
                continue;
            }
            $value = $values[$field->key] ?? null;
            if ($field->type->value === 'casilla') {
                $value = (bool) $value;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $out[] = ['key' => $field->key, 'label' => $field->label, 'type' => $field->type->value, 'value' => $value];
        }

        return $out;
    }
}
