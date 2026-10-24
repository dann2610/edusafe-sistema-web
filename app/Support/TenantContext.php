<?php

namespace App\Support;

use App\Models\Institution;
use Illuminate\Support\Facades\Auth;

/**
 * Institución en la que se ejecuta la petición actual.
 * - Usuario autenticado de una institución → su institución.
 * - Página pública → la institución resuelta desde la URL (ResolvePublicInstitution).
 * - Superadministrador, consola y colas → sin institución (sin filtro automático).
 *
 * Se usa para el alcance global de los modelos (BelongsToInstitution) y para
 * completar institution_id al crear registros.
 */
class TenantContext
{
    private ?int $forcedId = null;

    private ?Institution $forced = null;

    public function set(?Institution $institution): void
    {
        $this->forced = $institution;
        $this->forcedId = $institution?->id;
    }

    public function clear(): void
    {
        $this->forced = null;
        $this->forcedId = null;
    }

    public function id(): ?int
    {
        if ($this->forcedId !== null) {
            return $this->forcedId;
        }
        // hasUser() evita resolver el usuario (y recursión) si aún no se ha cargado
        $guard = Auth::guard();
        $user = $guard->hasUser() ? $guard->user() : null;

        return $user?->institution_id;
    }

    public function institution(): ?Institution
    {
        if ($this->forced) {
            return $this->forced;
        }
        $id = $this->id();

        return $id ? Institution::find($id) : null;
    }
}
