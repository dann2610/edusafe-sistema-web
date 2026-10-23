<x-layouts.app title="Periodos lectivos" heading="Periodos lectivos"
                lead="Solo un periodo puede estar vigente. Los nuevos casos y matrículas se asocian a él."
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Periodos lectivos' => null]">
    <x-slot:actions>
        <a href="{{ route('admin.periodos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo periodo</a>
    </x-slot:actions>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <caption class="visually-hidden">Periodos lectivos</caption>
                <thead><tr><th scope="col">Periodo</th><th scope="col" class="d-none d-md-table-cell">Fechas</th><th scope="col" class="d-none d-md-table-cell">Cursos / casos</th><th scope="col">Estado</th><th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                @forelse ($periods as $p)
                    <tr>
                        <td class="fw-semibold">{{ $p->name }}</td>
                        <td class="d-none d-md-table-cell small">{{ $p->starts_on->format('d/m/Y') }} – {{ $p->ends_on->format('d/m/Y') }}</td>
                        <td class="d-none d-md-table-cell small">{{ $p->courses_count }} / {{ $p->incidents_count }}</td>
                        <td>
                            @if ($p->is_current)<span class="badge text-bg-primary">Vigente</span>@endif
                            @if ($p->is_active)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Inactivo</span>@endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.periodos.edit', $p) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            @unless ($p->is_current)
                                <form method="POST" action="{{ route('admin.periodos.current', $p) }}" class="d-inline" data-confirm="¿Marcar «{{ $p->name }}» como periodo vigente?">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success" type="submit">Marcar vigente</button>
                                </form>
                                <form method="POST" action="{{ route('admin.periodos.toggle', $p) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $p->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">Aún no hay periodos. Registre el primero para habilitar cursos y matrículas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
