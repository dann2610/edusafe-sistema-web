<x-layouts.app title="Casos derivados" heading="Casos derivados a su entidad"
                :lead="$entity?->name ?? 'Su cuenta no está vinculada a una entidad.'"
                :breadcrumbs="['Casos derivados' => null]">
    <div class="alert alert-light border small" role="note">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
        Solo se muestran las derivaciones dirigidas a su entidad y la información que la institución decidió compartir. Cada consulta queda registrada.
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Derivaciones recibidas</caption>
                <thead><tr><th scope="col">Caso</th><th scope="col">Fecha</th><th scope="col" class="d-none d-md-table-cell">Medio</th><th scope="col">Gestión</th><th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                @forelse ($referrals as $r)
                    <tr>
                        <td><span class="fw-semibold">{{ $r->incident->code }}</span><div class="small">{{ $r->incident->category->name }}</div></td>
                        <td class="small">{{ $r->referred_on->format('d/m/Y') }}</td>
                        <td class="d-none d-md-table-cell small">{{ $r->channel->label() }}</td>
                        <td><span class="badge {{ $r->management_status->badgeClass() }}">{{ $r->management_status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('entity.referrals.show', $r) }}" class="btn btn-sm btn-outline-primary">Ver<span class="visually-hidden"> {{ $r->incident->code }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="bi-send" text="No hay derivaciones dirigidas a su entidad." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($referrals->hasPages())<div class="card-body border-top">{{ $referrals->links() }}</div>@endif
    </div>
</x-layouts.app>
