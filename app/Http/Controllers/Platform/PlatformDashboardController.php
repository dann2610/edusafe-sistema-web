<?php

namespace App\Http\Controllers\Platform;

use App\Enums\InstitutionStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Institution;
use Illuminate\View\View;

/**
 * Estadísticas generales de la plataforma: solo conteos por institución
 * y por categoría. Ningún dato personal ni contenido de casos.
 */
class PlatformDashboardController extends Controller
{
    public function __invoke(): View
    {
        $byStatus = Institution::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        $perInstitution = Incident::withoutInstitutionScope()->toBase()
            ->join('institutions as i', 'i.id', '=', 'incidents.institution_id')
            ->selectRaw('i.name AS institucion, i.slug')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(*) FILTER (WHERE incidents.status <> 'cerrado') AS abiertos")
            ->selectRaw("COUNT(*) FILTER (WHERE incidents.status = 'cerrado') AS cerrados")
            ->selectRaw("COUNT(*) FILTER (WHERE incidents.created_at >= NOW() - INTERVAL '30 days') AS ultimos_30")
            ->groupBy('i.name', 'i.slug')->orderBy('i.name')->get();

        $byCategory = Incident::withoutInstitutionScope()->toBase()
            ->join('incident_categories as c', 'c.id', '=', 'incidents.incident_category_id')
            ->selectRaw('c.name AS etiqueta, COUNT(*) AS total')
            ->groupBy('c.name')->orderByDesc('total')->limit(10)->pluck('total', 'etiqueta');

        return view('platform.dashboard', [
            'institutionsByStatus' => collect(InstitutionStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => (int) ($byStatus[$s->value] ?? 0)]),
            'perInstitution' => $perInstitution,
            'byCategory' => $byCategory,
            'totals' => [
                'instituciones' => Institution::count(),
                'casos' => (int) $perInstitution->sum('total'),
                'abiertos' => (int) $perInstitution->sum('abiertos'),
                'ultimos_30' => (int) $perInstitution->sum('ultimos_30'),
            ],
        ]);
    }
}
