<x-layouts.app title="Cursos" heading="Cursos"
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Cursos' => null]">
    <x-slot:actions>
        <a href="{{ route('admin.cursos.create', ['periodo' => $periodId]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo curso</a>
    </x-slot:actions>
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-8 col-md-5">
                    <label for="periodo" class="form-label small mb-1">Periodo lectivo</label>
                    <select id="periodo" name="periodo" class="form-select">
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" @selected($p->id === $periodId)>{{ $p->name }}{{ $p->is_current ? ' (vigente)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Ver</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <caption class="visually-hidden">Cursos del periodo</caption>
                <thead><tr><th scope="col">Curso</th><th scope="col" class="d-none d-md-table-cell">Tutor</th><th scope="col" class="d-none d-sm-table-cell">Estudiantes</th><th scope="col">Estado</th><th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                @forelse ($courses as $c)
                    <tr>
                        <td class="fw-semibold">{{ $c->display_name }}</td>
                        <td class="d-none d-md-table-cell">{{ $c->tutor?->name ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell">{{ $c->enrollments_count }}</td>
                        <td>@if ($c->is_active)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Inactivo</span>@endif</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.cursos.edit', $c) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            <form method="POST" action="{{ route('admin.cursos.toggle', $c) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $c->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">No hay cursos en este periodo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
