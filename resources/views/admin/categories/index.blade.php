<x-layouts.app title="Categorías" heading="Categorías de incidentes"
                :lead="$platform ? 'Categorías globales: aparecen en todas las instituciones.' : 'Las categorías globales las define la plataforma; puede crear categorías propias de su institución.'"
                :breadcrumbs="$platform ? ['Plataforma' => route('platform.dashboard'), 'Catálogos globales' => route('platform.catalogos.home'), 'Categorías' => null] : ['Catálogos' => route('admin.catalogos.home'), 'Categorías' => null]">
    <x-slot:actions>
        <a href="{{ route($prefix.'.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i>Nueva categoría</a>
    </x-slot:actions>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <caption class="visually-hidden">Categorías de incidentes</caption>
                <thead><tr><th scope="col">Categoría</th><th scope="col" class="d-none d-md-table-cell">Prioridad inicial</th><th scope="col" class="d-none d-lg-table-cell">Formulario público</th><th scope="col" class="text-end d-none d-sm-table-cell">Campos</th><th scope="col" class="text-end d-none d-sm-table-cell">Reportes</th><th scope="col">Estado</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                @forelse ($categories as $c)
                    @php $editable = $platform ? $c->isGlobal() : ! $c->isGlobal(); @endphp
                    <tr>
                        <td><span class="fw-semibold">{{ $c->name }}</span>
                            <div class="small text-secondary">{{ $c->code }} · @if ($c->isGlobal())<span class="badge text-bg-info">Global</span>@else<span class="badge text-bg-light">Institución</span>@endif
                                @if ($c->defaultAssignee) · Responsable: {{ $c->defaultAssignee->name }} @endif</div></td>
                        <td class="d-none d-md-table-cell"><x-priority-badge :priority="$c->default_priority" /></td>
                        <td class="d-none d-lg-table-cell">{{ $c->available_in_public_form ? 'Sí' : 'No' }}</td>
                        <td class="text-end tabular d-none d-sm-table-cell">{{ $c->fields_count }}</td>
                        <td class="text-end tabular d-none d-sm-table-cell">{{ $c->incidents_count }}</td>
                        <td>@if ($c->is_active)<span class="badge text-bg-success">Activa</span>@else<span class="badge text-bg-secondary">Inactiva</span>@endif</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route($prefix.'.edit', $c->id) }}" class="btn btn-sm btn-outline-primary">{{ $editable ? 'Editar' : 'Ver' }}<span class="visually-hidden"> {{ $c->name }}</span></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="bi-tags" title="No hay categorías" text="Cree la primera con «Nueva categoría»." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
