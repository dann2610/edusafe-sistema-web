<x-layouts.app title="Reportes" heading="Reportes institucionales"
                lead="Vista previa del reporte con los filtros seleccionados. Los reportes agregados no contienen datos personales."
                :breadcrumbs="['Reportes' => null]">
    <div class="card mb-3"><div class="card-body">@include('dashboard._filters', ['extra' => true])</div></div>

    @php $query = array_filter($filters); @endphp
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('reports.pdf', $query) }}" class="btn btn-primary"><i class="bi bi-filetype-pdf me-1" aria-hidden="true"></i>Descargar PDF</a>
        <a href="{{ route('reports.excel', $query) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Descargar Excel</a>
        @can('reportes.exportar_identificable')
            <a href="{{ route('reports.cases-excel', $query) }}" class="btn btn-outline-danger ms-md-auto"
               data-confirm-link="El listado de casos es información confidencial. La descarga quedará registrada en la bitácora. ¿Continuar?">
                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Listado de casos (confidencial)
            </a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Resumen</div>
                <table class="table table-sm mb-0">
                    <caption class="visually-hidden">Resumen</caption>
                    <tbody>
                    @foreach (app(\App\Services\ReportExporter::class)->summaryRows($summary) as $label => $value)
                        <tr><th scope="row" class="fw-normal ps-3">{{ $label }}</th><td class="text-end pe-3 fw-semibold">{{ $value }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="card-body small text-secondary border-top">
                    @foreach ($filterLabels as $k => $v)<div><strong>{{ $k }}:</strong> {{ $v }}</div>@endforeach
                </div>
            </div>
        </div>
        @foreach (['Por categoría' => $byCategory, 'Por estado' => $byStatus, 'Por curso' => $byCourse] as $title => $rows)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">{{ $title }}</div>
                    <table class="table table-sm mb-0">
                        <caption class="visually-hidden">{{ $title }}</caption>
                        <tbody>
                        @forelse ($rows as $label => $count)
                            <tr><th scope="row" class="fw-normal ps-3">{{ $label }}</th><td class="text-end pe-3">{{ $count }}</td></tr>
                        @empty
                            <tr><td class="text-secondary ps-3">Sin datos</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
