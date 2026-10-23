<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

/**
 * El personal de una entidad externa solo ve las derivaciones dirigidas
 * a la entidad vinculada a su cuenta.
 */
class ReferralPolicy
{
    public function viewAsEntity(User $user, Referral $referral): bool
    {
        return $user->hasPermission('derivaciones.ver_entidad')
            && $user->external_entity_id !== null
            && $referral->external_entity_id === $user->external_entity_id;
    }
}
