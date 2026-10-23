<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exportación de reportes.
 * - Reporte institucional (PDF/Excel): solo datos agregados, sin datos personales.
 * - Listado de casos (Excel): información identificable, restringido a
 *   reportes.exportar_identificable; incluye códigos y responsables, nunca
 *   nombres de estudiantes ni descripciones.
 * Toda celda de texto se neutraliza contra fórmulas (=, +, -, @).
 */
class ReportExporter
{
    public function __construct(private readonly StatisticsService $stats) {}

    public function data(array $filters): array
    {
        return [
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters),
            'summary' => $this->stats->summary($filters),
            'byCategory' => $this->stats->byCategory($filters),
            'byStatus' => $this->stats->byStatus($filters),
            'byCourse' => $this->stats->byCourse($filters),
            'monthly' => $this->stats->monthly($filters),
            'generatedAt' => now(),
        ];
    }

    public function pdf(array $filters): Response
    {
        $pdf = Pdf::loadView('reports.pdf', $this->data($filters))->setPaper('a4');

        return $pdf->download('reporte-institucional-'.now()->format('Ymd-His').'.pdf');
    }

    public function excel(array $filters): BinaryFileResponse
    {
        $d = $this->data($filters);
        $bold = (new Style)->setFontBold();
        $path = tempnam(sys_get_temp_dir(), 'rep').'.xlsx';

        $w = new Writer;
        $w->openToFile($path);
        $w->getCurrentSheet()->setName('Resumen');
        $w->addRow(Row::fromValues([$this->safe(config('seguridad_escolar.institucion.nombre'))], $bold));
        $w->addRow(Row::fromValues(['Reporte institucional de incidentes (datos agregados)']));
        $w->addRow(Row::fromValues(['Generado', $d['generatedAt']->format('d/m/Y H:i')]));
        foreach ($d['filterLabels'] as $label => $value) {
            $w->addRow(Row::fromValues([$this->safe($label), $this->safe($value)]));
        }
        $w->addRow(Row::fromValues([]));
        $w->addRow(Row::fromValues(['Indicador', 'Valor'], $bold));
        foreach ($this->summaryRows($d['summary']) as $label => $value) {
            $w->addRow(Row::fromValues([$label, $value]));
        }

        foreach (['Por categoría' => $d['byCategory'], 'Por estado' => $d['byStatus'], 'Por curso' => $d['byCourse'], 'Por mes' => $d['monthly']] as $title => $rows) {
            $w->addNewSheetAndMakeItCurrent()->setName($title);
            $w->addRow(Row::fromValues([str_replace('Por ', '', ucfirst($title)), 'Casos'], $bold));
            foreach ($rows as $label => $count) {
                $w->addRow(Row::fromValues([$this->safe((string) $label), $count]));
            }
        }
        $w->close();

        return response()->download($path, 'reporte-institucional-'.now()->format('Ymd-His').'.xlsx')->deleteFileAfterSend();
    }

    /** Listado de casos con información identificable (uso restringido). */
    public function casesExcel(array $filters): BinaryFileResponse
    {
        $bold = (new Style)->setFontBold();
        $path = tempnam(sys_get_temp_dir(), 'cas').'.xlsx';

        $w = new Writer;
        $w->openToFile($path);
        $w->getCurrentSheet()->setName('Casos');
        $w->addRow(Row::fromValues(['CONFIDENCIAL — uso exclusivo institucional. Generado el '.now()->format('d/m/Y H:i')], $bold));
        $w->addRow(Row::fromValues(['Código', 'Registrado', 'Fecha del hecho', 'Categoría', 'Curso', 'Estado', 'Prioridad', 'Origen', 'Responsables', 'Involucrados', 'Seguimientos', 'Derivaciones', 'Cerrado'], $bold));

        $this->stats->base($filters)
            ->with(['category', 'course', 'activeAssignments.user'])
            ->withCount(['participants', 'followUps', 'referrals'])
            ->orderBy('created_at')
            ->chunk(500, function ($chunk) use ($w) {
                foreach ($chunk as $i) {
                    $w->addRow(Row::fromValues(array_map(fn ($v) => is_string($v) ? $this->safe($v) : $v, [
                        $i->code,
                        $i->created_at->format('d/m/Y H:i'),
                        $i->occurred_at?->format('d/m/Y H:i') ?? '',
                        $i->category->name,
                        $i->course?->display_name ?? '',
                        $i->status->label(),
                        $i->priority->label(),
                        $i->source->label(),
                        $i->activeAssignments->pluck('user.name')->implode(', '),
                        $i->participants_count,
                        $i->follow_ups_count,
                        $i->referrals_count,
                        $i->closed_at?->format('d/m/Y') ?? '',
                    ])));
                }
            });
        $w->close();

        return response()->download($path, 'casos-confidencial-'.now()->format('Ymd-His').'.xlsx')->deleteFileAfterSend();
    }

    public function summaryRows(array $s): array
    {
        return [
            'Total de incidentes' => (int) $s['total'],
            'Casos abiertos' => (int) $s['abiertos'],
            'Pendientes de atención' => (int) $s['pendientes'],
            'Derivados' => (int) $s['derivados'],
            'Cerrados' => (int) $s['cerrados'],
            'Urgentes abiertos' => (int) $s['urgentes'],
            'Días promedio hasta el cierre' => $s['dias_cierre'] ?? '—',
        ];
    }

    public function filterLabels(array $f): array
    {
        $out = [];
        if (! empty($f['periodo'])) {
            $out['Periodo lectivo'] = AcademicPeriod::find($f['periodo'])?->name ?? '—';
        }
        if (! empty($f['categoria'])) {
            $out['Categoría'] = IncidentCategory::find($f['categoria'])?->name ?? '—';
        }
        if (! empty($f['curso'])) {
            $out['Curso'] = Course::find($f['curso'])?->display_name ?? '—';
        }
        if (! empty($f['estado'])) {
            $out['Estado'] = \App\Enums\CaseStatus::from($f['estado'])->label();
        }
        if (! empty($f['responsable'])) {
            $out['Responsable'] = User::find($f['responsable'])?->name ?? '—';
        }
        if (! empty($f['desde']) || ! empty($f['hasta'])) {
            $out['Fechas de registro'] = ($f['desde'] ?? '…').' a '.($f['hasta'] ?? '…');
        }

        return $out ?: ['Filtros' => 'Todos los registros'];
    }

    /** Neutraliza contenido que una hoja de cálculo interpretaría como fórmula. */
    public function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
