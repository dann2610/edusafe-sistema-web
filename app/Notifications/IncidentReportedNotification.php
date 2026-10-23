<?php

namespace App\Notifications;

use App\Enums\CasePriority;
use App\Models\Incident;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno al DECE de un nuevo reporte. Nunca incluye la descripción
 * ni datos de las personas involucradas: solo código, categoría y prioridad.
 */
class IncidentReportedNotification extends Notification
{
    public function __construct(public readonly Incident $incident) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $urgent = $this->incident->priority === CasePriority::Urgente;

        return [
            'codigo' => $this->incident->code,
            'tipo' => $urgent ? 'urgente' : 'nuevo',
            'mensaje' => ($urgent ? 'URGENTE: nuevo reporte con riesgo inmediato — ' : 'Nuevo reporte — ')
                .$this->incident->category->name.' ('.$this->incident->code.')',
            'url' => route('casos.show', $this->incident),
        ];
    }
}
