<x-layouts.app title="Estudiantes" heading="Estudiantes"
                :lead="$current ? 'Matrícula del periodo vigente: '.$current->name : 'No hay un periodo lectivo vigente.'"
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Estudiantes' => null]">
    <x-slot:actions>
        <a href="{{ route('admin.estudiantes.import') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1" aria-hidden="true"></i>Importar CSV</a>
        <a href="{{ route('admin.estudiantes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo estudiante</a>
    </x-slot:actions>
    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end" role="search">
                <div class="col-md-5">
                    <label for="q" class="form-label small mb-1">Buscar</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Código, nombres o apellidos" maxlength="100">
                </div>
                <div class="col-8 col-md-4">
                    <label for="curso" class="form-label small mb-1">Curso</label>
                    <select id="curso" name="curso" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($courses as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($filters['curso'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filtrar</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <caption class="visually-hidden">Estudiantes</caption>
                <thead><tr><th scope="col">Estudiante</th><th scope="col" class="d-none d-sm-table-cell">Código</th><th scope="col" class="d-none d-md-table-cell">Curso vigente</th><th scope="col">Estado</th><th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                @forelse ($students as $s)
                    <tr>
                        <td><span class="fw-semibold">{{ $s->full_name }}</span><div class="small text-secondary d-sm-none">{{ $s->institutional_code }}</div></td>
                        <td class="d-none d-sm-table-cell">{{ $s->institutional_code }}</td>
                        <td class="d-none d-md-table-cell">{{ $s->enrollments->first()?->course?->display_name ?? 'Sin matrícula' }}</td>
                        <td>@if ($s->is_active)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Inactivo</span>@endif</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.estudiantes.edit', $s) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            <form method="POST" action="{{ route('admin.estudiantes.toggle', $s) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $s->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">No se encontraron estudiantes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())<div class="card-body border-top">{{ $students->links() }}</div>@endif
    </div>
</x-layouts.app>
