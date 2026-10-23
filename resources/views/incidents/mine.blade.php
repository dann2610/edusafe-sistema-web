<x-layouts.app title="Mis reportes" heading="Mis reportes"
                lead="Incidentes que usted registró y su estado de atención."
                :breadcrumbs="['Mis reportes' => null]">
    @can('incidentes.registrar')
        <x-slot:actions>
            <a href="{{ route('incidentes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Registrar incidente</a>
        </x-slot:actions>
    @endcan
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Mis reportes</caption>
                <thead><tr><th scope="col">Código</th><th scope="col" class="d-none d-md-table-cell">Categoría</th><th scope="col">Estado</th><th scope="col" class="d-none d-sm-table-cell">Registrado</th><th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                @forelse ($reports as $r)
                    <tr>
                        <td class="fw-semibold text-nowrap">{{ $r->code }}<div class="small text-secondary d-md-none fw-normal">{{ $r->category->name }}</div></td>
                        <td class="d-none d-md-table-cell">{{ $r->category->name }}</td>
                        <td><x-status-badge :status="$r->status" /></td>
                        <td class="d-none d-sm-table-cell small">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end"><a href="{{ route('reportes.show', $r) }}" class="btn btn-sm btn-outline-primary">Ver<span class="visually-hidden"> {{ $r->code }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty text="Aún no ha registrado incidentes." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())<div class="card-body border-top">{{ $reports->links() }}</div>@endif
    </div>
</x-layouts.app>
