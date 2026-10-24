<x-layouts.app title="Bandeja de casos" heading="Bandeja de casos"
                lead="Los casos urgentes aparecen primero. Use los filtros para ubicar casos específicos."
                :breadcrumbs="['Bandeja de casos' => null]">
    <div class="row g-3 mb-3">
        @foreach ([['Nuevos', $counts->nuevos, 'registrado', '#6c757d'], ['Urgentes abiertos', $counts->urgentes, null, '#b42318'], ['Abiertos', $counts->abiertos, 'abiertos', 'var(--c-teal)'], ['Derivados', $counts->derivados, 'derivado', '#212529']] as [$label, $n, $estado, $color])
            <div class="col-6 col-lg-3">
                <a href="{{ $estado ? route('casos.inbox', ['estado' => $estado]) : route('casos.inbox', ['prioridad' => 'urgente']) }}" class="card stat-card h-100 text-decoration-none" style="--accent: {{ $color }}">
                    <div class="card-body py-3"><span class="stat-accent" aria-hidden="true"></span><div class="stat-value">{{ $n }}</div><div class="stat-label">{{ $label }}</div></div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end" role="search">
                <div class="col-6 col-md-2">
                    <label for="q" class="form-label small mb-1">Código</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="30" placeholder="UECRC-…">
                </div>
                <div class="col-6 col-md-2">
                    <label for="estado" class="form-label small mb-1">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="abiertos" @selected($filters['estado'] === 'abiertos')>Todos los abiertos</option>
                        @foreach (\App\Enums\CaseStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected($filters['estado'] === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="categoria" class="form-label small mb-1">Categoría</label>
                    <select id="categoria" name="categoria" class="form-select"><option value="">Todas</option>
                        @foreach ($categories as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['categoria'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="curso" class="form-label small mb-1">Curso</label>
                    <select id="curso" name="curso" class="form-select"><option value="">Todos</option>
                        @foreach ($courses as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['curso'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="responsable" class="form-label small mb-1">Responsable</label>
                    <select id="responsable" name="responsable" class="form-select"><option value="">Todos</option>
                        @foreach ($responsibles as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['responsable'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="prioridad" class="form-label small mb-1">Prioridad</label>
                    <select id="prioridad" name="prioridad" class="form-select"><option value="">Todas</option>
                        @foreach (\App\Enums\CasePriority::options() as $v => $n)<option value="{{ $v }}" @selected(($filters['prioridad'] ?? '') === $v)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="desde" class="form-label small mb-1">Desde</label>
                    <input type="date" id="desde" name="desde" value="{{ $filters['desde'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label for="hasta" class="form-label small mb-1">Hasta</label>
                    <input type="date" id="hasta" name="hasta" value="{{ $filters['hasta'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-grow-1" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                    <a href="{{ route('casos.inbox') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                </div>
            </form>
        </div>
        @include('cases._table', ['cases' => $cases])
    </div>
</x-layouts.app>
