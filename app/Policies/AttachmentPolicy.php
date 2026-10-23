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
        return $attachment->uploaded_by === $user->id
            || $user->can('viewCase', $attachment->incident);
    }
}
