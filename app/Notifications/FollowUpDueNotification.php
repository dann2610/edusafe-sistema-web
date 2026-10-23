<?php

namespace App\Notifications;

use App\Models\FollowUp;
use Illuminate\Notifications\Notification;

class FollowUpDueNotification extends Notification
{
    public function __construct(public readonly FollowUp $followUp) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $incident = $this->followUp->incident;

        return [
            'codigo' => $incident->code,
            'tipo' => 'recordatorio',
            'mensaje' => 'Seguimiento pendiente del caso '.$incident->code.' previsto para el '.$this->followUp->next_follow_up_on->format('d/m/Y'),
            'url' => route('casos.show', $incident),
            'follow_up_id' => $this->followUp->id,
        ];
    }
}
