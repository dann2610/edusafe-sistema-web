<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Casos</caption>
        <thead>
            <tr>
                <th scope="col">Caso</th>
                <th scope="col" class="d-none d-md-table-cell">Curso</th>
                <th scope="col">Estado</th>
                <th scope="col" class="d-none d-lg-table-cell">Responsables</th>
                <th scope="col" class="d-none d-sm-table-cell">Registrado</th>
                <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($cases as $c)
            <tr @class(['case-row-urgent' => $c->priority->value === 'urgente' && $c->status->isOpen()])>
                <td>
                    <div class="fw-semibold text-nowrap">{{ $c->code }}</div>
                    <div class="small">{{ $c->category->name }}</div>
                    <x-priority-badge :priority="$c->priority" class="mt-1" />
                    @if ($c->source->value === 'publico')<span class="badge text-bg-light border mt-1">Formulario público</span>@endif
                </td>
                <td class="d-none d-md-table-cell small">{{ $c->course?->display_name ?? '—' }}</td>
                <td><x-status-badge :status="$c->status" /></td>
                <td class="d-none d-lg-table-cell small">{{ $c->relationLoaded('activeAssignments') ? ($c->activeAssignments->pluck('user.name')->implode(', ') ?: 'Sin asignar') : '' }}</td>
                <td class="d-none d-sm-table-cell small text-nowrap">{{ $c->created_at->format('d/m/Y H:i') }}<div class="text-secondary">{{ $c->created_at->diffForHumans() }}</div></td>
                <td class="text-end"><a href="{{ route('casos.show', $c) }}" class="btn btn-sm btn-primary">Abrir<span class="visually-hidden"> {{ $c->code }}</span></a></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty text="No hay casos con esos criterios." /></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if ($cases->hasPages())<div class="card-body border-top">{{ $cases->links() }}</div>@endif
