<?php

namespace App\Services;

use App\Enums\AssignmentRole;
use App\Enums\AuditResult;
use App\Enums\CaseEventType;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\ComplianceStatus;
use App\Enums\ReferralStatus;
use App\Models\CaseAssignment;
use App\Models\CaseEvent;
use App\Models\CaseStatusHistory;
use App\Models\FollowUp;
use App\Models\Incident;
use App\Models\IncidentParticipant;
use App\Models\ProtectiveMeasure;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseStatusChangedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de la gestión de casos.
 * Cada operación: valida la regla, escribe en una transacción, deja
 * constancia en el historial del caso y en la bitácora, y notifica.
 * Ninguna asignación ni derivación cierra el caso automáticamente.
 */
class CaseWorkflow
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AttachmentStorage $storage,
    ) {}

    // ------------------------------------------------------------------ estados

    public function transition(Incident $incident, CaseStatus $target, User $actor, ?string $justification = null, ?string $closureSummary = null): void
    {
        $from = $incident->status;

        if (! $from->canTransitionTo($target)) {
            throw ValidationException::withMessages(['status' => "No se puede pasar de «{$from->label()}» a «{$target->label()}»."]);
        }
        if ($from->requiresJustificationTo($target) && blank($justification)) {
            throw ValidationException::withMessages(['justification' => 'Este cambio de estado requiere una justificación.']);
        }
        if ($target === CaseStatus::Cerrado && blank($closureSummary)) {
            throw ValidationException::withMessages(['closure_summary' => 'Para cerrar el caso debe registrar un resumen de cierre.']);
        }

        DB::transaction(function () use ($incident, $from, $target, $actor, $justification, $closureSummary) {
            $this->applyStatus($incident, $from, $target, $actor, $justification, $closureSummary);
        });

        $this->notifyStatusChange($incident, $from, $target, $actor);
    }

    /** Aplica un cambio de estado dentro de una transacción ya abierta. */
    private function applyStatus(Incident $incident, CaseStatus $from, CaseStatus $target, User $actor, ?string $justification, ?string $closureSummary = null): void
    {
        $attrs = ['status' => $target];
        if ($target === CaseStatus::Cerrado) {
            $attrs['closed_at'] = now();
            $attrs['closure_summary'] = $closureSummary;
        } elseif ($from === CaseStatus::Cerrado) {
            $attrs['closed_at'] = null; // reapertura: se conserva el resumen anterior en el historial
        }
        $incident->forceFill($attrs)->save();

        CaseStatusHistory::create([
            'incident_id' => $incident->id,
            'from_status' => $from->value,
            'to_status' => $target->value,
            'changed_by' => $actor->id,
            'justification' => $justification,
        ]);

        $type = match (true) {
            $target === CaseStatus::Cerrado => CaseEventType::Cerrado,
            $from === CaseStatus::Cerrado => CaseEventType::Reabierto,
            default => CaseEventType::EstadoCambiado,
        };
        $this->event($incident, $type, $actor, "Estado: {$from->label()} → {$target->label()}", null, ['de' => $from->value, 'a' => $target->value]);
        $this->audit->mustLog('caso_estado_cambiado', AuditResult::Exito, $incident, ['de' => $from->value, 'a' => $target->value], $actor);
    }

    public function changePriority(Incident $incident, CasePriority $priority, User $actor, string $reason): void
    {
        if ($incident->priority === $priority) {
            return;
        }
        $previous = $incident->priority;

        DB::transaction(function () use ($incident, $priority, $previous, $actor, $reason) {
            $incident->forceFill(['priority' => $priority])->save();
            $this->event($incident, CaseEventType::EstadoCambiado, $actor, "Prioridad: {$previous->label()} → {$priority->label()}. Motivo: {$reason}", null, ['prioridad_anterior' => $previous->value, 'prioridad' => $priority->value]);
            $this->audit->mustLog('caso_prioridad_cambiada', AuditResult::Exito, $incident, ['de' => $previous->value, 'a' => $priority->value], $actor);
        });
    }

    // -------------------------------------------------------------- asignaciones

    public function assign(Incident $incident, User $assignee, AssignmentRole $role, User $actor, ?string $notes = null): CaseAssignment
    {
        $this->ensureOpen($incident);
        if (! $assignee->is_active || ! $assignee->hasPermission('seguimientos.registrar')) {
            throw ValidationException::withMessages(['user_id' => 'La persona seleccionada no puede ser responsable de casos.']);
        }
        if ($incident->isAssignedTo($assignee)) {
            throw ValidationException::withMessages(['user_id' => 'La persona ya tiene una asignación activa en este caso.']);
        }

        $statusChanged = null;
        $assignment = DB::transaction(function () use ($incident, $assignee, $role, $actor, $notes, &$statusChanged) {
            $assignment = $incident->assignments()->make(['user_id' => $assignee->id, 'assignment_role' => $role->value, 'notes' => $notes]);
            $assignment->assigned_by = $actor->id;
            $assignment->assigned_at = now();
            $assignment->save();

            $this->event($incident, CaseEventType::Asignado, $actor, "Asignado a {$assignee->name} ({$role->label()})", $assignment);
            $this->audit->mustLog('caso_asignado', AuditResult::Exito, $incident, ['responsable_id' => $assignee->id], $actor);

            // Un caso en revisión pasa a "Asignado" al recibir su primer responsable
            if ($incident->status === CaseStatus::EnRevision) {
                $this->applyStatus($incident, CaseStatus::EnRevision, CaseStatus::Asignado, $actor, 'Asignación de responsable.');
                $statusChanged = [CaseStatus::EnRevision, CaseStatus::Asignado];
            }

            return $assignment;
        });

        $assignee->notify(new CaseAssignedNotification($incident));
        if ($statusChanged) {
            $this->notifyStatusChange($incident, $statusChanged[0], $statusChanged[1], $actor);
        }

        return $assignment;
    }

    public function unassign(CaseAssignment $assignment, User $actor, string $reason): void
    {
        if (! $assignment->isActive()) {
            throw ValidationException::withMessages(['reason' => 'La asignación ya fue finalizada.']);
        }

        DB::transaction(function () use ($assignment, $actor, $reason) {
            $assignment->forceFill(['unassigned_at' => now(), 'unassigned_by' => $actor->id, 'unassignment_reason' => $reason])->save();
            $this->event($assignment->incident, CaseEventType::AsignacionLiberada, $actor, "Finalizó la asignación de {$assignment->user->name}. Motivo: {$reason}", $assignment);
            $this->audit->mustLog('caso_asignacion_liberada', AuditResult::Exito, $assignment->incident, ['responsable_id' => $assignment->user_id], $actor);
        });
    }

    // --------------------------------------------------------------- involucrados

    public function addParticipant(Incident $incident, array $data, User $actor): IncidentParticipant
    {
        return DB::transaction(function () use ($incident, $data, $actor) {
            $participant = $incident->participants()->make($data);
            $participant->registered_by = $actor->id;
            $participant->save();
            $this->event($incident, CaseEventType::ParticipanteAgregado, $actor, 'Se agregó una persona involucrada ('.$participant->role->label().')', $participant);
            $this->audit->mustLog('caso_involucrado_agregado', AuditResult::Exito, $incident, [], $actor);

            return $participant;
        });
    }

    // --------------------------------------------------------------- seguimientos

    /** @param array<int, UploadedFile> $files */
    public function addFollowUp(Incident $incident, array $data, User $actor, array $files = [], ?FollowUp $corrects = null): FollowUp
    {
        if (! $corrects) {
            $this->ensureOpen($incident);
        }

        $statusChanged = null;
        $followUp = DB::transaction(function () use ($incident, $data, $actor, $files, $corrects, &$statusChanged) {
            $followUp = $incident->followUps()->make([
                'follow_up_type_id' => $data['follow_up_type_id'],
                'performed_at' => $data['performed_at'],
                'description' => $data['description'],
                'observations' => $data['observations'] ?? null,
                'next_action' => $data['next_action'] ?? null,
                'next_follow_up_on' => $data['next_follow_up_on'] ?? null,
                'corrects_follow_up_id' => $corrects?->id,
                'correction_reason' => $corrects ? $data['correction_reason'] : null,
            ]);
            $followUp->user_id = $actor->id;
            $followUp->save();

            foreach ($files as $file) {
                $this->storage->store($file, $incident, $followUp, $actor);
            }

            $summary = $corrects
                ? "Corrección del seguimiento del {$corrects->performed_at->format('d/m/Y')}. Motivo: {$data['correction_reason']}"
                : 'Seguimiento registrado: '.$followUp->type->name;
            $this->event($incident, $corrects ? CaseEventType::Correccion : CaseEventType::Seguimiento, $actor, $summary, $followUp);
            $this->audit->mustLog($corrects ? 'seguimiento_corregido' : 'seguimiento_registrado', AuditResult::Exito, $incident, [], $actor);

            // El primer seguimiento de un caso asignado lo pasa a "En seguimiento"
            if (! $corrects && $incident->status === CaseStatus::Asignado) {
                $this->applyStatus($incident, CaseStatus::Asignado, CaseStatus::EnSeguimiento, $actor, 'Primer seguimiento registrado.');
                $statusChanged = [CaseStatus::Asignado, CaseStatus::EnSeguimiento];
            }

            return $followUp;
        });

        if ($statusChanged) {
            $this->notifyStatusChange($incident, $statusChanged[0], $statusChanged[1], $actor);
        }

        return $followUp;
    }

    // ------------------------------------------------------------------- medidas

    public function addMeasure(Incident $incident, array $data, User $actor): ProtectiveMeasure
    {
        $this->ensureOpen($incident);

        return DB::transaction(function () use ($incident, $data, $actor) {
            $measure = $incident->measures()->make($data + ['compliance_status' => ComplianceStatus::Pendiente->value]);
            $measure->registered_by = $actor->id;
            $measure->save();
            $this->event($incident, CaseEventType::MedidaRegistrada, $actor, 'Medida registrada: '.$measure->type->name.' ('.$measure->type->kind->label().')', $measure);
            $this->audit->mustLog('medida_registrada', AuditResult::Exito, $incident, [], $actor);

            return $measure;
        });
    }

    public function updateMeasure(ProtectiveMeasure $measure, array $data, User $actor): void
    {
        DB::transaction(function () use ($measure, $data, $actor) {
            $previous = $measure->compliance_status;
            $measure->fill($data);
            $changes = array_keys($measure->getDirty());
            if (! $changes) {
                return;
            }
            $measure->save();
            $text = $previous !== $measure->compliance_status
                ? "Medida «{$measure->type->name}»: {$previous->label()} → {$measure->compliance_status->label()}"
                : "Medida «{$measure->type->name}» actualizada";
            $this->event($measure->incident, CaseEventType::MedidaActualizada, $actor, $text, $measure, ['campos' => $changes]);
            $this->audit->mustLog('medida_actualizada', AuditResult::Exito, $measure->incident, ['campos' => $changes], $actor);
        });
    }

    // -------------------------------------------------------------- derivaciones

    public function addReferral(Incident $incident, array $data, User $actor): Referral
    {
        $this->ensureOpen($incident);
        $statusChanged = null;

        $referral = DB::transaction(function () use ($incident, $data, $actor, &$statusChanged) {
            $referral = $incident->referrals()->make($data + ['management_status' => ReferralStatus::Enviada->value]);
            $referral->registered_by = $actor->id;
            $referral->save();
            $this->event($incident, CaseEventType::Derivacion, $actor, 'Derivado a '.$referral->externalEntity->name.' por '.$referral->channel->label(), $referral);
            $this->audit->mustLog('caso_derivado', AuditResult::Exito, $incident, ['entidad_id' => $referral->external_entity_id], $actor);

            if ($incident->status->canTransitionTo(CaseStatus::Derivado)) {
                $from = $incident->status;
                $this->applyStatus($incident, $from, CaseStatus::Derivado, $actor, 'Derivación a '.$referral->externalEntity->name.'.');
                $statusChanged = [$from, CaseStatus::Derivado];
            }

            return $referral;
        });

        if ($statusChanged) {
            $this->notifyStatusChange($incident, $statusChanged[0], $statusChanged[1], $actor);
        }

        return $referral;
    }

    public function updateReferral(Referral $referral, array $data, User $actor): void
    {
        DB::transaction(function () use ($referral, $data, $actor) {
            $previous = $referral->management_status;
            $referral->fill($data);
            $changes = array_keys($referral->getDirty());
            if (! $changes) {
                return;
            }
            $referral->save();
            $this->event($referral->incident, CaseEventType::DerivacionActualizada, $actor,
                "Derivación a {$referral->externalEntity->name}: {$previous->label()} → {$referral->management_status->label()}", $referral, ['campos' => $changes]);
            $this->audit->mustLog('derivacion_actualizada', AuditResult::Exito, $referral->incident, ['campos' => $changes], $actor);
        });
    }

    // ------------------------------------------------------------------ adjuntos

    /** @param array<int, UploadedFile> $files */
    public function addAttachments(Incident $incident, array $files, User $actor): void
    {
        DB::transaction(function () use ($incident, $files, $actor) {
            foreach ($files as $file) {
                $this->storage->store($file, $incident, $incident, $actor);
            }
            $this->event($incident, CaseEventType::Adjunto, $actor, count($files).' evidencia(s) adjunta(s)');
            $this->audit->mustLog('evidencia_adjuntada', AuditResult::Exito, $incident, ['cantidad' => count($files)], $actor);
        });
    }

    // ------------------------------------------------------------------ apoyo

    private function ensureOpen(Incident $incident): void
    {
        if (! $incident->status->isOpen()) {
            throw ValidationException::withMessages(['status' => 'El caso está cerrado. Reábralo para registrar nuevas actuaciones.']);
        }
    }

    private function event(Incident $incident, CaseEventType $type, User $actor, string $summary, ?Model $subject = null, array $metadata = []): void
    {
        CaseEvent::create([
            'incident_id' => $incident->id,
            'event_type' => $type->value,
            'user_id' => $actor->id,
            'summary' => mb_substr($summary, 0, 500),
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
        ]);
    }

    /** Notifica a responsables activos y a quien registró el caso (si es usuario interno). */
    private function notifyStatusChange(Incident $incident, CaseStatus $from, CaseStatus $to, User $actor): void
    {
        $recipients = $incident->activeAssignments()->with('user')->get()->pluck('user')
            ->push($incident->reporter)
            ->filter(fn ($u) => $u && $u->is_active && $u->id !== $actor->id)
            ->unique('id');

        Notification::send($recipients, new CaseStatusChangedNotification($incident, $from, $to));
    }
}
