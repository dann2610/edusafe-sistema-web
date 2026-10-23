<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\IncidentCategory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportExporter $exporter, private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('reports.index', $this->exporter->data($filters) + [
            'periods' => AcademicPeriod::orderByDesc('starts_on')->pluck('name', 'id')->all(),
            'categories' => IncidentCategory::orderBy('name')->pluck('name', 'id')->all(),
            'courses' => Course::with('academicPeriod')->orderBy('level')->get()->mapWithKeys(fn ($c) => [$c->id => $c->display_name.' ('.$c->academicPeriod->name.')'])->all(),
            'statuses' => CaseStatus::options(),
            'responsibles' => User::active()->whereHas('caseAssignments')->orderBy('name')->pluck('name', 'id')->all(),
            'summaryRows' => null,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);
        $this->audit->success('reporte_exportado', null, ['formato' => 'pdf', 'tipo' => 'agregado']);

        return $this->exporter->pdf($filters);
    }

    public function excel(Request $request): Response
    {
        $filters = $this->filters($request);
        $this->audit->success('reporte_exportado', null, ['formato' => 'xlsx', 'tipo' => 'agregado']);

        return $this->exporter->excel($filters);
    }

    /** Exportación con información identificable: permiso específico y auditoría. */
    public function casesExcel(Request $request): Response
    {
        $filters = $this->filters($request);
        $this->audit->success('reporte_exportado', null, ['formato' => 'xlsx', 'tipo' => 'identificable', 'filtros' => array_filter($filters)]);

        return $this->exporter->casesExcel($filters);
    }

    private function filters(Request $request): array
    {
        $f = $request->validate([
            'periodo' => ['nullable', 'integer'],
            'categoria' => ['nullable', 'integer'],
            'curso' => ['nullable', 'integer'],
            'estado' => ['nullable', Rule::enum(CaseStatus::class)],
            'responsable' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
        if (! $request->has('periodo')) {
            $f['periodo'] = AcademicPeriod::current()?->id;
        }

        return $f;
    }
}
