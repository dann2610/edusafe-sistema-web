<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentRole;
use App\Enums\ComplianceStatus;
use App\Enums\ReferralChannel;
use App\Enums\ReferralStatus;
use App\Models\CaseAssignment;
use App\Models\FollowUp;
use App\Models\Incident;
use App\Models\ProtectiveMeasure;
use App\Models\Referral;
use App\Models\User;
use App\Services\AttachmentStorage;
use App\Services\CaseWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Actuaciones sobre un caso: asignaciones, seguimientos (y correcciones),
 * medidas y derivaciones. La lógica de negocio vive en CaseWorkflow.
 * Todas las rutas verifican que el subregistro pertenezca al caso de la URL.
 */
class CaseActionController extends Controller
{
    public function __construct(private readonly CaseWorkflow $workflow) {}

    // ---------------------------------------------------------- asignaciones

    public function assign(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('assign', $incident);

        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'assignment_role' => ['required', Rule::enum(AssignmentRole::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['user_id' => 'el responsable', 'assignment_role' => 'el tipo de responsabilidad']);

        $this->workflow->assign($incident, User::findOrFail($data['user_id']), AssignmentRole::from($data['assignment_role']), $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Responsable asignado y notificado.');
    }

    public function release(Request $request, Incident $incident, CaseAssignment $assignment): RedirectResponse
    {
        Gate::authorize('assign', $incident);
        abort_unless($assignment->incident_id === $incident->id, 404);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'el motivo']);
        $this->workflow->unassign($assignment, $request->user(), $data['reason']);

        return back()->with('success', 'Asignación finalizada.');
    }

    // ---------------------------------------------------------- seguimientos

    public function followUp(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('addFollowUp', $incident);

        $data = $request->validate($this->followUpRules(), [], $this->followUpAttributes());
        $this->workflow->addFollowUp($incident, $data, $request->user(), $request->file('attachments', []));

        return back()->withFragment('pane-seguimientos')->with('success', 'Seguimiento registrado.');
    }

    public function correctFollowUp(Request $request, Incident $incident, FollowUp $followUp): RedirectResponse
    {
        Gate::authorize('addFollowUp', $incident);
        abort_unless($followUp->incident_id === $incident->id, 404);
        // Solo DECE o quien registró el seguimiento pueden corregirlo
        abort_unless($request->user()->hasPermission('casos.gestionar') || $followUp->user_id === $request->user()->id, 403);

        $data = $request->validate(
            $this->followUpRules() + ['correction_reason' => ['required', 'string', 'min:5', 'max:500']],
            [], $this->followUpAttributes() + ['correction_reason' => 'el motivo de la corrección'],
        );
        $this->workflow->addFollowUp($incident, $data, $request->user(), $request->file('attachments', []), $followUp);

        return back()->withFragment('pane-seguimientos')->with('success', 'Corrección registrada. El registro original se conserva en el historial.');
    }

    private function followUpRules(): array
    {
        return [
            'follow_up_type_id' => ['required', 'integer', Rule::exists('follow_up_types', 'id')->where('is_active', true)],
            'performed_at' => ['required', 'date', 'before_or_equal:now'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'next_action' => ['nullable', 'string', 'max:500'],
            'next_follow_up_on' => ['nullable', 'date', 'after_or_equal:today'],
        ] + AttachmentStorage::validationRules();
    }

    private function followUpAttributes(): array
    {
        return [
            'follow_up_type_id' => 'el tipo de actuación', 'performed_at' => 'la fecha de la actuación',
            'description' => 'la descripción', 'observations' => 'las observaciones',
            'next_action' => 'la próxima acción', 'next_follow_up_on' => 'la fecha del próximo seguimiento',
            'attachments.*' => 'el archivo adjunto',
        ];
    }

    // ---------------------------------------------------------------- medidas

    public function storeMeasure(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manageMeasures', $incident);

        $data = $request->validate([
            'measure_type_id' => ['required', 'integer', Rule::exists('measure_types', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'responsible_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'starts_on' => ['required', 'date'],
            'review_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'measure_type_id' => 'el tipo de medida', 'responsible_user_id' => 'el responsable', 'starts_on' => 'la fecha de inicio',
            'review_on' => 'la fecha de revisión', 'ends_on' => 'la fecha de finalización', 'description' => 'la descripción',
        ]);

        $this->workflow->addMeasure($incident, $data, $request->user());

        return back()->withFragment('pane-medidas')->with('success', 'Medida registrada.');
    }

    public function updateMeasure(Request $request, Incident $incident, ProtectiveMeasure $measure): RedirectResponse
    {
        Gate::authorize('manageMeasures', $incident);
        abort_unless($measure->incident_id === $incident->id, 404);

        $data = $request->validate([
            'compliance_status' => ['required', Rule::enum(ComplianceStatus::class)],
            'review_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:'.$measure->starts_on->format('Y-m-d')],
            'observations' => ['nullable', 'string', 'max:2000'],
        ], [], ['compliance_status' => 'el estado de cumplimiento', 'ends_on' => 'la fecha de finalización']);

        $this->workflow->updateMeasure($measure, $data, $request->user());

        return back()->withFragment('pane-medidas')->with('success', 'Medida actualizada.');
    }

    // ----------------------------------------------------------- derivaciones

    public function storeReferral(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manageReferrals', $incident);

        $data = $request->validate([
            'external_entity_id' => ['required', 'integer', Rule::exists('external_entities', 'id')->where('is_active', true)],
            'referred_on' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
            'channel' => ['required', Rule::enum(ReferralChannel::class)],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'shared_summary' => ['nullable', 'string', 'max:3000'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'external_entity_id' => 'la entidad', 'referred_on' => 'la fecha de derivación', 'reason' => 'el motivo',
            'channel' => 'el medio', 'reference_number' => 'el número de oficio o referencia',
            'shared_summary' => 'la información compartida',
        ]);

        $this->workflow->addReferral($incident, $data, $request->user());

        return back()->withFragment('pane-derivaciones')->with('success', 'Derivación registrada.');
    }

    public function updateReferral(Request $request, Incident $incident, Referral $referral): RedirectResponse
    {
        Gate::authorize('manageReferrals', $incident);
        abort_unless($referral->incident_id === $incident->id, 404);

        $data = $request->validate([
            'management_status' => ['required', Rule::enum(ReferralStatus::class)],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ], [], ['management_status' => 'el estado de la gestión']);

        $this->workflow->updateReferral($referral, $data, $request->user());

        return back()->withFragment('pane-derivaciones')->with('success', 'Derivación actualizada.');
    }
}
