<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Models\AcademicPeriod;
use App\Models\IncidentCategory;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Dashboard de indicadores agregados (sin datos personales). */
class DashboardController extends Controller
{
    public function __invoke(Request $request, StatisticsService $stats): View
    {
        $filters = $request->validate([
            'periodo' => ['nullable', 'integer'],
            'categoria' => ['nullable', 'integer'],
            'estado' => ['nullable', Rule::enum(CaseStatus::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
        if (! $request->has('periodo')) {
            $filters['periodo'] = AcademicPeriod::current()?->id;
        }

        return view('dashboard.index', [
            'filters' => $filters,
            'summary' => $stats->summary($filters),
            'charts' => [
                'categorias' => $stats->byCategory($filters),
                'estados' => $stats->byStatus($filters),
                'mensual' => $stats->monthly($filters),
                'prioridad' => $stats->openByPriority($filters),
            ],
            'periods' => AcademicPeriod::orderByDesc('starts_on')->pluck('name', 'id')->all(),
            'categories' => IncidentCategory::orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => CaseStatus::options(),
        ]);
    }
}
