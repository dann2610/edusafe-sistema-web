<?php

namespace App\Http\Controllers;

use App\Support\ScopedRule;
use App\Enums\AssignmentRole;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\ComplianceStatus;
use App\Enums\ExternalEntityType;
use App\Enums\ParticipantRole;
use App\Enums\ReferralChannel;
use App\Enums\ReferralStatus;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExternalEntity;
use App\Models\FollowUpType;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\MeasureType;
use App\Models\User;
use App\Services\AttachmentStorage;
use App\Services\AuditLogger;
use App\Services\CaseWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function __construct(private readonly CaseWorkflow $workflow) {}

    /** Bandeja del DECE con filtros. */
    public function inbox(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:30'],
            'estado' => ['nullable', 'string', 'in:abiertos,'.implode(',', CaseStatus::values())],
            'categoria' => ['nullable', 'integer'],
            'curso' => ['nullable', 'integer'],
            'responsable' => ['nullable', 'integer'],
            'prioridad' => ['nullable', Rule::enum(CasePriority::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
        $filters['estado'] ??= 'abiertos';

        $cases = $this->filteredQuery($filters)
            ->with(['category', 'course', 'activeAssignments.user'])
            ->inboxOrder()
            ->paginate(20)->withQueryString();

        $counts = Incident::toBase()
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'registrado') AS nuevos")
            ->selectRaw("COUNT(*) FILTER (WHERE status <> 'cerrado' AND priority = 'urgente') AS urgentes")
            ->selectRaw("COUNT(*) FILTER (WHERE status <> 'cerrado') AS abiertos")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'derivado') AS derivados")
            ->first();

        return view('cases.inbox', [
            'cases' => $cases,
            'filters' => $filters,
            'counts' => $counts,
            'categories' => IncidentCategory::orderBy('name')->pluck('name', 'id')->all(),
            'courses' => $this->currentCourses(),
            'responsibles' => User::active()->ofInstitution($request->user()->institution_id)->whereHas('caseAssignments')->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    /** Casos asignados a la persona (docentes responsables y DECE). */
    public function assigned(Request $request): View
    {
        $cases = Incident::with(['category', 'course'])
            ->whereHas('activeAssignments', fn ($q) => $q->where('user_id', $request->user()->id))
            ->inboxOrder()
            ->paginate(20);

        return view('cases.assigned', compact('cases'));
    }

    public function show(Request $request, Incident $incident): View
    {
        Gate::authorize('viewCase', $incident);
        app(AuditLogger::class)->success('expediente_consultado', $incident);

        $incident->load([
            'category', 'course', 'academicPeriod', 'reporter',
            'participants.student', 'attachments.uploader',
            'assignments' => fn ($q) => $q->with(['user', 'assignedBy', 'unassignedBy'])->latest('assigned_at'),
            'followUps' => fn ($q) => $q->with(['type', 'user', 'attachments', 'corrections', 'corrects']),
            'measures' => fn ($q) => $q->with(['type', 'responsible'])->latest('starts_on'),
            'referrals' => fn ($q) => $q->with(['externalEntity', 'registeredBy'])->latest('referred_on'),
            'events' => fn ($q) => $q->with('user'),
            'notes.user',
        ]);

        $user = $request->user();
        $data = ['incident' => $incident, 'transitions' => $incident->status->allowedTransitions()];

        if ($user->can('assign', $incident)) {
            $data['assignables'] = User::active()->ofInstitution($incident->institution_id)->whereHas('role.permissions', fn ($q) => $q->where('slug', 'seguimientos.registrar'))
                ->whereNotIn('id', $incident->assignments->whereNull('unassigned_at')->pluck('user_id'))
                ->with('role')->orderBy('name')->get()
                ->mapWithKeys(fn ($u) => [$u->id => $u->name.' — '.$u->role->name])->all();
        }
        if ($user->can('addFollowUp', $incident)) {
            $data['followUpTypes'] = FollowUpType::active()->orderBy('name')->pluck('name', 'id')->all();
        }
        if ($user->can('manageMeasures', $incident)) {
            $data['measureTypes'] = MeasureType::active()->orderBy('kind')->orderBy('name')->get()
                ->mapWithKeys(fn ($t) => [$t->id => $t->name.' ('.$t->kind->label().')'])->all();
            $data['responsibles'] = User::active()->ofInstitution($incident->institution_id)->whereHas('role', fn ($q) => $q->whereIn('slug', ['dece', 'docente', 'autoridad']))->orderBy('name')->pluck('name', 'id')->all();
        }
        if ($user->can('manageReferrals', $incident)) {
            $data['entities'] = ExternalEntity::active()->orderBy('name')->pluck('name', 'id')->all();
        }
        if ($user->can('manage', $incident)) {
            $data['studentsByCourse'] = $this->studentsByCourse();
        }

        return view('cases.show', $data + [
            'priorities' => CasePriority::options(),
            'assignmentRoles' => AssignmentRole::options(),
            'participantRoles' => ParticipantRole::options(),
            'compliance' => ComplianceStatus::options(),
            'channels' => ReferralChannel::options(),
            'referralStatuses' => ReferralStatus::options(),
            'entityTypes' => ExternalEntityType::options(),
        ]);
    }

    public function transition(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manage', $incident);

        $data = $request->validate([
            'status' => ['required', Rule::enum(CaseStatus::class)],
            'justification' => ['nullable', 'string', 'max:2000'],
            'closure_summary' => ['nullable', 'string', 'max:5000'],
        ], [], ['justification' => 'la justificación', 'closure_summary' => 'el resumen de cierre']);

        $target = CaseStatus::from($data['status']);
        $this->workflow->transition($incident, $target, $request->user(), $data['justification'] ?? null, $data['closure_summary'] ?? null);

        return back()->with('success', "El caso pasó a «{$target->label()}».");
    }

    public function priority(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manage', $incident);

        $data = $request->validate([
            'priority' => ['required', Rule::enum(CasePriority::class)],
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ], [], ['priority' => 'la prioridad', 'reason' => 'el motivo']);

        $this->workflow->changePriority($incident, CasePriority::from($data['priority']), $request->user(), $data['reason']);

        return back()->with('success', 'Prioridad actualizada.');
    }

    public function addParticipant(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('manage', $incident);

        $data = $request->validate([
            'student_id' => ['nullable', 'integer', ScopedRule::own('students')->where('is_active', true), 'required_without:non_student_description'],
            'non_student_description' => ['nullable', 'string', 'max:150'],
            'role' => ['required', Rule::enum(ParticipantRole::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], ['student_id.required_without' => 'Seleccione un estudiante o describa a la persona.'], ['role' => 'el rol']);

        if (! empty($data['student_id']) && $incident->participants()->where('student_id', $data['student_id'])->where('role', $data['role'])->exists()) {
            return back()->withErrors(['student_id' => 'El estudiante ya está registrado con ese rol.']);
        }

        $this->workflow->addParticipant($incident, $data, $request->user());

        return back()->with('success', 'Persona involucrada agregada.');
    }

    public function addAttachments(Request $request, Incident $incident): RedirectResponse
    {
        Gate::authorize('addAttachments', $incident);

        $rules = AttachmentStorage::validationRules();
        $rules['attachments'] = ['required', 'array', 'max:'.config('seguridad_escolar.adjuntos.max_archivos')];
        $request->validate($rules, [], ['attachments' => 'las evidencias', 'attachments.*' => 'la evidencia']);
        $this->workflow->addAttachments($incident, $request->file('attachments'), $request->user());

        return back()->with('success', 'Evidencias adjuntadas.');
    }

    private function filteredQuery(array $f): Builder
    {
        return Incident::query()
            ->when($f['q'] ?? null, fn ($q, $code) => $q->where('code', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $code).'%'))
            ->when(($f['estado'] ?? null) === 'abiertos', fn ($q) => $q->open())
            ->when(($f['estado'] ?? null) && $f['estado'] !== 'abiertos', fn ($q) => $q->where('status', $f['estado']))
            ->when($f['categoria'] ?? null, fn ($q, $v) => $q->where('incident_category_id', $v))
            ->when($f['curso'] ?? null, fn ($q, $v) => $q->where('course_id', $v))
            ->when($f['prioridad'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($f['responsable'] ?? null, fn ($q, $v) => $q->whereHas('activeAssignments', fn ($a) => $a->where('user_id', $v)))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    private function currentCourses(): array
    {
        $current = AcademicPeriod::current();

        return $current ? Course::where('academic_period_id', $current->id)->orderBy('level')->orderBy('parallel')->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->display_name])->all() : [];
    }

    private function studentsByCourse(): array
    {
        $current = AcademicPeriod::current();
        if (! $current) {
            return [];
        }

        return Enrollment::with(['student', 'course'])->where('academic_period_id', $current->id)
            ->whereHas('student', fn ($q) => $q->where('is_active', true))->get()
            ->sortBy(fn ($e) => $e->course->level.$e->course->parallel.$e->student->last_names)
            ->groupBy(fn ($e) => $e->course->display_name)
            ->map(fn ($g) => $g->mapWithKeys(fn ($e) => [$e->student->id => $e->student->full_name])->all())->all();
    }
}
