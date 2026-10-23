<?php

namespace App\Services;

use App\Enums\CaseStatus;
use App\Models\Incident;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Indicadores agregados calculados directamente en PostgreSQL.
 * Solo devuelve conteos: nunca nombres, descripciones ni identificadores
 * de estudiantes.
 */
class StatisticsService
{
    /** @param array{periodo?: ?int, categoria?: ?int, estado?: ?string, curso?: ?int, desde?: ?string, hasta?: ?string, responsable?: ?int} $f */
    public function base(array $f): Builder
    {
        return Incident::query()
            ->when($f['periodo'] ?? null, fn ($q, $v) => $q->where('incidents.academic_period_id', $v))
            ->when($f['categoria'] ?? null, fn ($q, $v) => $q->where('incidents.incident_category_id', $v))
            ->when($f['estado'] ?? null, fn ($q, $v) => $q->where('incidents.status', $v))
            ->when($f['curso'] ?? null, fn ($q, $v) => $q->where('incidents.course_id', $v))
            ->when($f['responsable'] ?? null, fn ($q, $v) => $q->whereHas('activeAssignments', fn ($a) => $a->where('user_id', $v)))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('incidents.created_at', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('incidents.created_at', '<=', $v));
    }

    public function summary(array $f): array
    {
        $row = $this->base($f)->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(*) FILTER (WHERE status <> 'cerrado') AS abiertos")
            ->selectRaw("COUNT(*) FILTER (WHERE status IN ('registrado','en_revision','requiere_informacion')) AS pendientes")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'derivado') AS derivados")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'cerrado') AS cerrados")
            ->selectRaw("COUNT(*) FILTER (WHERE status <> 'cerrado' AND priority = 'urgente') AS urgentes")
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (closed_at - created_at)) / 86400) FILTER (WHERE status = 'cerrado')::numeric, 1) AS dias_cierre")
            ->first();

        return array_map(fn ($v) => $v === null ? null : (is_numeric($v) ? $v + 0 : $v), (array) $row);
    }

    /** @return array<string, int> nombre de categoría => cantidad */
    public function byCategory(array $f): array
    {
        return $this->base($f)->toBase()
            ->join('incident_categories as c', 'c.id', '=', 'incidents.incident_category_id')
            ->selectRaw('c.name AS etiqueta, COUNT(*) AS total')
            ->groupBy('c.name')
            ->orderByDesc('total')->orderBy('etiqueta')
            ->pluck('total', 'etiqueta')
            ->map(fn ($v) => (int) $v)->all();
    }

    /** @return array<string, int> etiqueta del estado => cantidad (todos los estados) */
    public function byStatus(array $f): array
    {
        $counts = $this->base($f)->toBase()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $out = [];
        foreach (CaseStatus::cases() as $s) {
            $out[$s->label()] = (int) ($counts[$s->value] ?? 0);
        }

        return $out;
    }

    /** @return array<string, int> curso => cantidad */
    public function byCourse(array $f): array
    {
        return $this->base($f)->toBase()
            ->leftJoin('courses as co', 'co.id', '=', 'incidents.course_id')
            ->selectRaw("COALESCE(co.level || ' «' || co.parallel || '»', 'Sin curso') AS etiqueta, COUNT(*) AS total")
            ->groupByRaw("COALESCE(co.level || ' «' || co.parallel || '»', 'Sin curso')")
            ->orderByDesc('total')->orderBy('etiqueta')
            ->pluck('total', 'etiqueta')
            ->map(fn ($v) => (int) $v)->all();
    }

    /** Serie mensual completa (incluye meses sin casos) de los últimos $months meses o del rango filtrado. */
    public function monthly(array $f, int $months = 12): array
    {
        $to = isset($f['hasta']) ? CarbonImmutable::parse($f['hasta'])->startOfMonth() : CarbonImmutable::now()->startOfMonth();
        $from = isset($f['desde']) ? CarbonImmutable::parse($f['desde'])->startOfMonth() : $to->subMonths($months - 1);
        if ($from->greaterThan($to)) {
            $from = $to;
        }

        $raw = $this->base($f)->toBase()
            ->whereDate('incidents.created_at', '>=', $from->toDateString())
            ->selectRaw("to_char(date_trunc('month', incidents.created_at), 'YYYY-MM') AS mes, COUNT(*) AS total")
            ->groupBy('mes')->pluck('total', 'mes');

        $out = [];
        for ($m = $from; $m->lessThanOrEqualTo($to); $m = $m->addMonth()) {
            $out[$m->translatedFormat('M Y')] = (int) ($raw[$m->format('Y-m')] ?? 0);
        }

        return $out;
    }

    /** @return array<string, int> prioridad => cantidad (solo casos abiertos) */
    public function openByPriority(array $f): array
    {
        $counts = $this->base($f)->open()->toBase()->selectRaw('priority, COUNT(*) AS total')->groupBy('priority')->pluck('total', 'priority');

        return ['Urgente' => (int) ($counts['urgente'] ?? 0), 'Alta' => (int) ($counts['alta'] ?? 0), 'Normal' => (int) ($counts['normal'] ?? 0)];
    }
}
