<x-layouts.app title="Instituciones" heading="Instituciones"
                lead="Las instituciones no se eliminan: se suspenden o desactivan y conservan su historial."
                :breadcrumbs="['Plataforma' => route('platform.dashboard'), 'Instituciones' => null]">
    <x-slot:actions>
        <a href="{{ route('platform.institutions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i>Registrar institución</a>
    </x-slot:actions>

    <div class="card">
        <form method="GET" class="toolbar row g-2 m-0" role="search">
            <div class="col-md-5"><label for="q" class="visually-hidden">Buscar</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nombre, siglas o código AMIE" maxlength="100"></div>
            <div class="col-sm-6 col-md-3"><label for="estado" class="visually-hidden">Estado</label>
                <select id="estado" name="estado" class="form-select"><option value="">Todos los estados</option>
                    @foreach (\App\Enums\InstitutionStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['estado'] ?? '') === $v)>{{ $l }}</option>@endforeach
                </select></div>
            <div class="col-sm-6 col-md-2"><label for="provincia" class="visually-hidden">Provincia</label>
                <select id="provincia" name="provincia" class="form-select"><option value="">Provincia</option>
                    @foreach ($provinces as $p)<option value="{{ $p }}" @selected(($filters['provincia'] ?? '') === $p)>{{ $p }}</option>@endforeach
                </select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel" aria-hidden="true"></i>Filtrar</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <caption class="visually-hidden">Instituciones registradas</caption>
                <thead><tr><th scope="col">Institución</th><th scope="col" class="d-none d-md-table-cell">Ubicación</th><th scope="col" class="d-none d-lg-table-cell">AMIE</th><th scope="col" class="text-end d-none d-sm-table-cell">Usuarios</th><th scope="col" class="text-end d-none d-sm-table-cell">Casos abiertos</th><th scope="col">Estado</th></tr></thead>
                <tbody>
                @forelse ($institutions as $inst)
                    <tr>
                        <td><a href="{{ route('platform.institutions.show', $inst) }}" class="fw-semibold text-decoration-none">{{ $inst->name }}</a>
                            <div class="small text-secondary">{{ $inst->code_prefix }}@if ($inst->is_default) · por defecto @endif</div></td>
                        <td class="d-none d-md-table-cell">{{ $inst->location() ?: '—' }}</td>
                        <td class="d-none d-lg-table-cell tabular">{{ $inst->amie_code ?: '—' }}</td>
                        <td class="text-end tabular d-none d-sm-table-cell">{{ $inst->users_count }}</td>
                        <td class="text-end tabular d-none d-sm-table-cell">{{ $openCases[$inst->id] ?? 0 }}</td>
                        <td><span class="badge {{ $inst->status->badgeClass() }}">{{ $inst->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="bi-building" title="No hay instituciones con esos criterios" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($institutions->hasPages())<div class="card-footer">{{ $institutions->links() }}</div>@endif
    </div>
</x-layouts.app>
