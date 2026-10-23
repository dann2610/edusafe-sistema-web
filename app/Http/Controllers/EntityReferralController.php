<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Vista del personal de entidades externas (Policía/DINAPEN).
 * Solo ve las derivaciones dirigidas a su entidad y la información que el DECE
 * decidió compartir (shared_summary). Nunca la descripción ni los involucrados.
 */
class EntityReferralController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('externalEntity');

        $referrals = Referral::with(['incident.category'])
            ->where('external_entity_id', $user->external_entity_id ?? 0)
            ->latest('referred_on')->latest('id')
            ->paginate(20);

        return view('referrals.entity-index', ['referrals' => $referrals, 'entity' => $user->externalEntity]);
    }

    public function show(Referral $referral, AuditLogger $audit): View
    {
        Gate::authorize('viewAsEntity', $referral);

        $referral->load(['incident.category', 'externalEntity']);
        $audit->success('derivacion_consultada_externa', $referral->incident, ['derivacion_id' => $referral->id]);

        return view('referrals.entity-show', compact('referral'));
    }
}
