<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Notifications\Notification;

class CaseAssignedNotification extends Notification
{
    public function __construct(public readonly Incident $incident) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'codigo' => $this->incident->code,
            'tipo' => 'asignacion',
            'mensaje' => 'Se le asignó el caso '.$this->incident->code.' ('.$this->incident->category->name.')',
            'url' => route('casos.show', $this->incident),
        ];
    }
}
