<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\ParticipantRole;
use App\Http\Requests\StoreIncidentRequest;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Services\IncidentRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Registro interno de incidentes y consulta de los reportes propios.
 * Quien registra ve el estado de su reporte, no el expediente completo.
 */
class IncidentController extends Controller
{
    public function create(): View
    {
        $current = AcademicPeriod::current();

        return view('incidents.create', [
            'categories' => IncidentCategory::active()->ordered()->get(),
            'courses' => $current
                ? Course::where('academic_period_id', $current->id)->active()->orderBy('level')->orderBy('parallel')->get()->mapWithKeys(fn ($c) => [$c->id => $c->display_name])->all()
                : [],
            'studentsByCourse' => $this->studentsByCourse($current),
            'roles' => ParticipantRole::options(),
            'maxFiles' => config('seguridad_escolar.adjuntos.max_archivos'),
            'maxMb' => round(config('seguridad_escolar.adjuntos.max_kb') / 1024, 1),
        ]);
    }

    public function store(StoreIncidentRequest $request, IncidentRegistrar $registrar): RedirectResponse
    {
        $incident = $registrar->registerInternal(
            $request->user(),
            $request->safe()->except(['participants', 'attachments']),
            $request->validated('participants', []),
            $request->file('attachments', []),
        );

        return redirect()->route('reportes.show', $incident)
            ->with('success', "Incidente registrado con el código {$incident->code}. El DECE fue notificado.");
    }

    public function myReports(Request $request): View
    {
        $reports = Incident::with('category')
            ->where('reporter_user_id', $request->user()->id)
            ->latest()->paginate(15);

        return view('incidents.mine', compact('reports'));
    }

    public function showOwn(Incident $incident): View
    {
        Gate::authorize('viewOwnReport', $incident);

        $incident->load(['category', 'course', 'participants.student', 'attachments']);
        $statusHistory = $incident->statusHistory()->get();

        return view('incidents.show-own', compact('incident', 'statusHistory'));
    }

    /** @return array<string, array<int, string>> curso => [id => nombre] */
    private function studentsByCourse(?AcademicPeriod $current): array
    {
        if (! $current) {
            return [];
        }

        return Enrollment::with(['student', 'course'])
            ->where('academic_period_id', $current->id)
            ->whereHas('student', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($e) => $e->course->level.$e->course->parallel.$e->student->last_names)
            ->groupBy(fn ($e) => $e->course->display_name)
            ->map(fn ($group) => $group->mapWithKeys(fn ($e) => [$e->student->id => $e->student->full_name])->all())
            ->all();
    }
}
