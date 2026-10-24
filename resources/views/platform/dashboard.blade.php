<x-layouts.app title="Plataforma" heading="Resumen de la plataforma"
                lead="Solo cifras agregadas. El superadministrador no accede a expedientes ni a datos personales de los casos."
                :breadcrumbs="['Plataforma' => null]">
    <x-slot:actions>
        <a href="{{ route('platform.institutions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i>Registrar institución</a>
    </x-slot:actions>

    <div class="row g-3 mb-4">
        @foreach ([['Instituciones', $totals['instituciones'], 'var(--c-ink)'], ['Casos registrados', $totals['casos'], 'var(--c-teal)'], ['Casos abiertos', $totals['abiertos'], '#c98a04'], ['Últimos 30 días', $totals['ultimos_30'], 'var(--c-info)']] as [$label, $value, $accent])
            <div class="col-6 col-xl-3"><div class="card stat-card h-100" style="--accent: {{ $accent }}"><div class="card-body"><span class="stat-accent" aria-hidden="true"></span>
                <div class="stat-value">{{ number_format($value) }}</div><div class="stat-label">{{ $label }}</div></div></div></div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-building" aria-hidden="true"></i>Casos por institución</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th scope="col">Institución</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">Abiertos</th><th scope="col" class="text-end">Cerrados</th><th scope="col" class="text-end">30 días</th></tr></thead>
                        <tbody>
                        @forelse ($perInstitution as $row)
                            <tr>
                                <td><a href="{{ route('platform.institutions.show', $row->slug) }}" class="fw-semibold text-decoration-none">{{ $row->institucion }}</a></td>
                                <td class="text-end tabular">{{ $row->total }}</td><td class="text-end tabular">{{ $row->abiertos }}</td>
                                <td class="text-end tabular">{{ $row->cerrados }}</td><td class="text-end tabular">{{ $row->ultimos_30 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty icon="bi-bar-chart" title="Aún no hay casos" text="Las cifras aparecerán cuando las instituciones registren reportes." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4 d-grid gap-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-flag" aria-hidden="true"></i>Instituciones por estado</div>
                <ul class="list-group list-group-flush">
                    @foreach ($institutionsByStatus as $label => $n)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $label }}</span><span class="fw-semibold tabular">{{ $n }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="card">
                <div class="card-header"><i class="bi bi-tags" aria-hidden="true"></i>Categorías más reportadas</div>
                <ul class="list-group list-group-flush">
                    @forelse ($byCategory as $label => $n)
                        <li class="list-group-item d-flex justify-content-between gap-2"><span>{{ $label }}</span><span class="fw-semibold tabular">{{ $n }}</span></li>
                    @empty
                        <li class="list-group-item"><x-empty icon="bi-tags" text="Sin datos." /></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
