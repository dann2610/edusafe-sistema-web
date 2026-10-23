{{-- Filtros compartidos por el dashboard y los reportes. $extra agrega curso y responsable. --}}
<form method="GET" class="row g-2 align-items-end">
    <div class="col-6 col-md-3 col-xl-2">
        <label for="periodo" class="form-label small mb-1">Periodo lectivo</label>
        <select id="periodo" name="periodo" class="form-select form-select-sm"><option value="">Todos</option>
            @foreach ($periods as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['periodo'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label for="categoria" class="form-label small mb-1">Categoría</label>
        <select id="categoria" name="categoria" class="form-select form-select-sm"><option value="">Todas</option>
            @foreach ($categories as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['categoria'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
        </select>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label for="estado" class="form-label small mb-1">Estado</label>
        <select id="estado" name="estado" class="form-select form-select-sm"><option value="">Todos</option>
            @foreach ($statuses as $v => $n)<option value="{{ $v }}" @selected(($filters['estado'] ?? '') === $v)>{{ $n }}</option>@endforeach
        </select>
    </div>
    @if ($extra ?? false)
        <div class="col-6 col-md-3 col-xl-2">
            <label for="curso" class="form-label small mb-1">Curso</label>
            <select id="curso" name="curso" class="form-select form-select-sm"><option value="">Todos</option>
                @foreach ($courses as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['curso'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <label for="responsable" class="form-label small mb-1">Responsable</label>
            <select id="responsable" name="responsable" class="form-select form-select-sm"><option value="">Todos</option>
                @foreach ($responsibles as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['responsable'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
            </select>
        </div>
    @endif
    <div class="col-6 col-md-3 col-xl-2">
        <label for="desde" class="form-label small mb-1">Registrado desde</label>
        <input type="date" id="desde" name="desde" value="{{ $filters['desde'] ?? '' }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label for="hasta" class="form-label small mb-1">Hasta</label>
        <input type="date" id="hasta" name="hasta" value="{{ $filters['hasta'] ?? '' }}" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Aplicar</button>
    </div>
</form>
