<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

/**
 * Acceso a casos (expedientes).
 * - DECE (casos.ver_todos): expediente completo de cualquier caso.
 * - Responsable asignado activo: expediente del caso asignado.
 * - Quien registró el reporte: solo la vista limitada de su reporte (viewOwnReport).
 * - Administrador, autoridad, policía y superadministrador: sin acceso a expedientes.
 * - Ningún usuario accede a casos de otra institución.
 */
class IncidentPolicy
{
    /** Defensa en profundidad: además del alcance de datos, se exige la misma institución. */
    private function sameInstitution(User $user, Incident $incident): bool
    {
        return $user->institution_id !== null && $incident->institution_id === $user->institution_id;
    }

    public function viewCase(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && (
            $user->hasPermission('casos.ver_todos')
            || ($user->hasPermission('seguimientos.registrar') && $incident->isAssignedTo($user))
        );
    }

    public function viewOwnReport(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident)
            && $user->hasPermission('incidentes.ver_propios') && $incident->reporter_user_id === $user->id;
    }

    /** Quien registró el caso puede aportar información adicional mientras esté abierto. */
    public function addNote(User $user, Incident $incident): bool
    {
        return $this->viewOwnReport($user, $incident) && $incident->status->isOpen();
    }

    public function manage(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && $user->hasPermission('casos.gestionar');
    }

    public function assign(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && $user->hasPermission('casos.asignar') && $incident->status->isOpen();
    }

    public function addFollowUp(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && $user->hasPermission('seguimientos.registrar')
            && ($user->hasPermission('casos.ver_todos') || $incident->isAssignedTo($user));
    }

    /** Un responsable asignado (no DECE) puede escalar el caso al DECE. */
    public function escalate(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && $incident->status->isOpen()
            && ! $user->hasPermission('casos.gestionar') && $incident->isAssignedTo($user);
    }

    public function manageMeasures(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && $user->hasPermission('medidas.gestionar');
    }

    public function manageReferrals(User $user, Incident $incident): bool
    {
        return $this->sameInstitution($user, $incident) && $user->hasPermission('derivaciones.gestionar');
    }

    public function addAttachments(User $user, Incident $incident): bool
    {
        return $user->hasPermission('adjuntos.subir') && $this->viewCase($user, $incident);
    }
}
