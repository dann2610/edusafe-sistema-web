<x-layouts.app title="Dashboard" heading="Indicadores de convivencia escolar"
                lead="Datos agregados calculados en tiempo real a partir de los casos registrados. No incluyen datos personales."
                :breadcrumbs="['Dashboard' => null]">
    @can('reportes.generar')
        <x-slot:actions>
            <a href="{{ route('reports.index', array_filter($filters)) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-arrow-down me-1" aria-hidden="true"></i>Generar reporte</a>
        </x-slot:actions>
    @endcan

    <div class="card mb-3"><div class="card-body">@include('dashboard._filters')</div></div>

    <div class="row g-3 mb-3">
        @foreach ([
            ['Total registrados', $summary['total'], 'var(--tic-navy)'],
            ['Pendientes de atención', $summary['pendientes'], '#c98a04'],
            ['Urgentes abiertos', $summary['urgentes'], '#b42318'],
            ['Derivados', $summary['derivados'], '#212529'],
            ['Cerrados', $summary['cerrados'], '#198754'],
            ['Días promedio al cierre', $summary['dias_cierre'] ?? '—', 'var(--tic-teal)'],
        ] as [$label, $value, $color])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card h-100" style="border-left-color:{{ $color }}">
                    <div class="card-body py-3"><div class="stat-value">{{ $value }}</div><div class="stat-label">{{ $label }}</div></div>
                </div>
            </div>
        @endforeach
    </div>

    @if ((int) $summary['total'] === 0)
        <div class="card"><x-empty icon="bi-bar-chart" text="No hay casos registrados con los filtros seleccionados." /></div>
    @else
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header">Evolución mensual de reportes</div>
                    <div class="card-body"><div class="chart-box"><canvas id="chart-mensual" role="img" aria-label="Gráfico de líneas: reportes por mes"></canvas></div></div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">Casos por estado</div>
                    <div class="card-body"><div class="chart-box"><canvas id="chart-estados" role="img" aria-label="Gráfico circular: casos por estado"></canvas></div></div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-header">Incidentes por categoría</div>
                    <div class="card-body"><div class="chart-box"><canvas id="chart-categorias" role="img" aria-label="Gráfico de barras: incidentes por categoría"></canvas></div></div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">Casos abiertos por prioridad</div>
                    <div class="card-body"><div class="chart-box"><canvas id="chart-prioridad" role="img" aria-label="Gráfico de barras: casos abiertos por prioridad"></canvas></div></div>
                </div>
            </div>
        </div>

        {{-- Tablas equivalentes para lectores de pantalla y para quien prefiera ver los números --}}
        <details class="card mt-3">
            <summary class="card-header">Ver los datos en tablas</summary>
            <div class="card-body row g-3">
                @foreach (['Por categoría' => $charts['categorias'], 'Por estado' => $charts['estados'], 'Por mes' => $charts['mensual']] as $title => $rows)
                    <div class="col-md-4">
                        <table class="table table-sm small">
                            <caption class="caption-top fw-semibold">{{ $title }}</caption>
                            <tbody>@foreach ($rows as $k => $v)<tr><th scope="row" class="fw-normal">{{ $k }}</th><td class="text-end">{{ $v }}</td></tr>@endforeach</tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </details>

        <script type="application/json" id="dashboard-data">@json($charts)</script>
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
        <script src="{{ asset('js/dashboard.js') }}"></script>
    @endif
</x-layouts.app>
