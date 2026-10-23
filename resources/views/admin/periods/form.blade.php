<x-layouts.app title="Periodo lectivo" :heading="$period ? 'Editar periodo' : 'Nuevo periodo lectivo'"
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Periodos lectivos' => route('admin.periodos.index'), ($period ? 'Editar' : 'Nuevo') => null]">
    <div class="card"><div class="card-body p-4">
        <form method="POST" novalidate action="{{ $period ? route('admin.periodos.update', $period) : route('admin.periodos.store') }}">
            @csrf @if ($period) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6"><x-form.input name="name" label="Nombre" :value="$period?->name" required maxlength="50" help="Ejemplo: 2026-2027 Costa." /></div>
                <div class="col-md-3"><x-form.input name="starts_on" type="date" label="Inicio" :value="$period?->starts_on?->format('Y-m-d')" required /></div>
                <div class="col-md-3"><x-form.input name="ends_on" type="date" label="Finalización" :value="$period?->ends_on?->format('Y-m-d')" required /></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Guardar</button>
                <a href="{{ route('admin.periodos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div></div>
</x-layouts.app>
