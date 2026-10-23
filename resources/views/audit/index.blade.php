<x-layouts.app title="Bitácora" heading="Bitácora de auditoría"
                lead="Registro inmutable de las operaciones críticas. No contiene contraseñas ni descripciones de denuncias."
                :breadcrumbs="['Bitácora' => null]">
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label for="accion" class="form-label small mb-1">Acción</label>
                    <select id="accion" name="accion" class="form-select form-select-sm"><option value="">Todas</option>
                        @foreach ($actions as $v => $n)<option value="{{ $v }}" @selected(($filters['accion'] ?? '') === $v)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="usuario" class="form-label small mb-1">Usuario</label>
                    <select id="usuario" name="usuario" class="form-select form-select-sm"><option value="">Todos</option>
                        @foreach ($users as $id => $n)<option value="{{ $id }}" @selected((string) ($filters['usuario'] ?? '') === (string) $id)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="resultado" class="form-label small mb-1">Resultado</label>
                    <select id="resultado" name="resultado" class="form-select form-select-sm"><option value="">Todos</option>
                        @foreach ($results as $v => $n)<option value="{{ $v }}" @selected(($filters['resultado'] ?? '') === $v)>{{ $n }}</option>@endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2"><label for="desde" class="form-label small mb-1">Desde</label><input type="date" id="desde" name="desde" value="{{ $filters['desde'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-6 col-md-1"><label for="hasta" class="form-label small mb-1">Hasta</label><input type="date" id="hasta" name="hasta" value="{{ $filters['hasta'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-6 col-md-1"><button class="btn btn-sm btn-primary w-100" type="submit">Filtrar</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <caption class="visually-hidden">Registros de auditoría</caption>
                <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col" class="d-none d-md-table-cell">Registro</th><th scope="col">Resultado</th><th scope="col" class="d-none d-lg-table-cell">IP</th><th scope="col" class="d-none d-xl-table-cell">Detalle</th></tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($log->action)) }}</td>
                        <td class="d-none d-md-table-cell">{{ $log->auditable_type ? $log->auditable_type.' #'.$log->auditable_id : '—' }}</td>
                        <td><span class="badge {{ ['exito' => 'text-bg-success', 'denegado' => 'text-bg-warning', 'fallido' => 'text-bg-danger'][$log->result->value] }}">{{ $log->result->label() }}</span></td>
                        <td class="d-none d-lg-table-cell">{{ $log->ip_address ?? '—' }}</td>
                        <td class="d-none d-xl-table-cell text-secondary">{{ $log->metadata ? \Illuminate\Support\Str::limit(json_encode($log->metadata, JSON_UNESCAPED_UNICODE), 80) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="bi-clock-history" text="No hay registros con esos criterios." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())<div class="card-body border-top">{{ $logs->links() }}</div>@endif
    </div>
</x-layouts.app>
