<?php

namespace App\Notifications;

use App\Enums\CaseStatus;
use App\Models\Incident;
use Illuminate\Notifications\Notification;

/**
 * Cambio de estado. Quien registró el caso recibe el enlace a su reporte
 * (vista limitada); los responsables, el enlace al expediente.
 */
class CaseStatusChangedNotification extends Notification
{
    public function __construct(
        public readonly Incident $incident,
        public readonly CaseStatus $from,
        public readonly CaseStatus $to,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isReporterOnly = $this->incident->reporter_user_id === $notifiable->id
            && ! $this->incident->isAssignedTo($notifiable)
            && ! $notifiable->hasPermission('casos.ver_todos');

        return [
            'codigo' => $this->incident->code,
            'tipo' => 'estado',
            'mensaje' => 'El caso '.$this->incident->code.' pasó a «'.$this->to->label().'»',
            'url' => $isReporterOnly ? route('reportes.show', $this->incident) : route('casos.show', $this->incident),
        ];
    }
}
