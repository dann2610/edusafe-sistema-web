<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

/**
 * Descarga de evidencias: quien puede ver el expediente del caso,
 * o la persona que subió el archivo.
 */
class AttachmentPolicy
{
    public function download(User $user, Attachment $attachment): bool
    {
        // incident se carga con el alcance de institución: si es de otra institución es null
        $incident = $attachment->incident;
        if (! $incident || $incident->institution_id !== $user->institution_id) {
            return false;
        }

        return $attachment->uploaded_by === $user->id || $user->can('viewCase', $incident);
    }
}
