<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Notifications\Notification;

/** Aviso interno genérico sobre un caso (sin descripción ni datos personales). */
class CaseMessageNotification extends Notification
{
    public function __construct(
        public readonly Incident $incident,
        public readonly string $type,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'codigo' => $this->incident->code,
            'tipo' => $this->type,
            'mensaje' => $this->message,
            'url' => route('casos.show', $this->incident),
        ];
    }
}
