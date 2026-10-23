<?php

namespace App\Services;

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
use App\Models\User;
use App\Notifications\IncidentReportedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Registro de incidentes (interno y público).
 * La prioridad inicial solo refleja lo comunicado por quien reporta:
 * - riesgo inmediato indicado → urgente
 * - categoría marcada como sensible → alta
 * El sistema no evalúa la veracidad del reporte.
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
        return $this->register(ReportSource::Interno, $data, $reporter, $participants, $files);
    }

    public function registerPublic(array $data): Incident
    {
        return $this->register(ReportSource::Publico, $data, null);
    }

    private function register(ReportSource $source, array $data, ?User $reporter, array $participants = [], array $files = []): Incident
    {
        $incident = DB::transaction(function () use ($source, $data, $reporter, $participants, $files) {
            $category = IncidentCategory::findOrFail($data['incident_category_id']);

            $incident = new Incident([
                'incident_category_id' => $category->id,
                'academic_period_id' => AcademicPeriod::current()?->id,
                'course_id' => $data['course_id'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? null,
                'occurred_at_is_approximate' => true,
                'location' => $data['location'] ?? null,
                'description' => $data['description'],
                'immediate_risk' => (bool) ($data['immediate_risk'] ?? false),
                'reporter_relationship' => $source === ReportSource::Publico ? ($data['reporter_relationship'] ?? null) : null,
                'reporter_contact_name' => $source === ReportSource::Publico ? ($data['reporter_contact_name'] ?? null) : null,
                'reporter_contact' => $source === ReportSource::Publico ? ($data['reporter_contact'] ?? null) : null,
                'privacy_notice_accepted' => $source === ReportSource::Publico ? true : false,
            ]);
            $incident->forceFill([
                'code' => Incident::generateCode(),
                'status' => CaseStatus::Registrado,
                'priority' => $this->initialPriority($incident->immediate_risk, $category),
                'source' => $source,
                'reporter_user_id' => $reporter?->id,
            ])->save();

            CaseStatusHistory::create([
                'incident_id' => $incident->id,
                'from_status' => null,
                'to_status' => CaseStatus::Registrado->value,
                'changed_by' => $reporter?->id,
                'justification' => $source === ReportSource::Publico ? 'Reporte recibido por el formulario público.' : null,
            ]);
            CaseEvent::create([
                'incident_id' => $incident->id,
                'event_type' => CaseEventType::Creado->value,
                'user_id' => $reporter?->id,
                'summary' => $source === ReportSource::Publico ? 'Reporte recibido por el formulario público' : 'Incidente registrado por '.$reporter->name,
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

            $this->audit->mustLog(
                $source === ReportSource::Publico ? 'reporte_publico_recibido' : 'incidente_registrado',
                AuditResult::Exito, $incident, ['codigo' => $incident->code], $reporter,
            );

            return $incident;
        });

        // Notificación interna al DECE (fuera de la transacción)
        $dece = User::active()->withRole(RoleSlug::Dece)->get();
        Notification::send($dece, new IncidentReportedNotification($incident));

        return $incident;
    }

    private function initialPriority(bool $immediateRisk, IncidentCategory $category): CasePriority
    {
        return match (true) {
            $immediateRisk => CasePriority::Urgente,
            $category->suggests_high_priority => CasePriority::Alta,
            default => CasePriority::Normal,
        };
    }
}
