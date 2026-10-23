<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

/**
 * Acceso a casos (expedientes).
 * - DECE (casos.ver_todos): expediente completo de cualquier caso.
 * - Responsable asignado activo: expediente del caso asignado.
 * - Quien registró el reporte: solo la vista limitada de su reporte (viewOwnReport).
 * - Administrador técnico, autoridad y policía: sin acceso a expedientes.
 */
class IncidentPolicy
{
    public function viewCase(User $user, Incident $incident): bool
    {
        return $user->hasPermission('casos.ver_todos')
            || ($user->hasPermission('seguimientos.registrar') && $incident->isAssignedTo($user));
    }

    public function viewOwnReport(User $user, Incident $incident): bool
    {
        return $user->hasPermission('incidentes.ver_propios') && $incident->reporter_user_id === $user->id;
    }

    public function manage(User $user, Incident $incident): bool
    {
        return $user->hasPermission('casos.gestionar');
    }

    public function assign(User $user, Incident $incident): bool
    {
        return $user->hasPermission('casos.asignar') && $incident->status->isOpen();
    }

    public function addFollowUp(User $user, Incident $incident): bool
    {
        return $user->hasPermission('seguimientos.registrar')
            && ($user->hasPermission('casos.ver_todos') || $incident->isAssignedTo($user));
    }

    public function manageMeasures(User $user, Incident $incident): bool
    {
        return $user->hasPermission('medidas.gestionar');
    }

    public function manageReferrals(User $user, Incident $incident): bool
    {
        return $user->hasPermission('derivaciones.gestionar');
    }

    public function addAttachments(User $user, Incident $incident): bool
    {
        return $user->hasPermission('adjuntos.subir') && $this->viewCase($user, $incident);
    }
}
