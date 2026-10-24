<x-layouts.app :title="$def['title']" :heading="$def['title']"
                lead="{{ $platform ? 'Registros globales: los ven todas las instituciones.' : 'Los registros globales los define la plataforma y son de solo lectura. Los registros no se eliminan: se desactivan.' }}"
                :breadcrumbs="['Catálogos' => route($prefix.'.home'), $def['title'] => null]">
    <x-slot:actions>
        <a href="{{ route($prefix.'.create', $def['key']) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nuevo registro</a>
    </x-slot:actions>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2" role="search">
                <div class="col-sm-8 col-md-6">
                    <label for="q" class="visually-hidden">Buscar por nombre</label>
                    <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Buscar por nombre" maxlength="100">
                </div>
                <div class="col-sm-4 col-md-2"><button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Buscar</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">{{ $def['title'] }}</caption>
                <thead>
                    <tr>
                        @foreach ($def['columns'] as $col => $label)
                            <th scope="col" @class(['d-none d-md-table-cell' => ! $loop->first])>{{ $label }}</th>
                        @endforeach
                        <th scope="col">Ámbito</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            @foreach ($def['columns'] as $col => $label)
                                <td @class(['d-none d-md-table-cell' => ! $loop->first, 'fw-semibold' => $loop->first])>
                                    {{ \Illuminate\Support\Str::limit(\App\Support\CatalogRegistry::display($item->{$col}), 80) }}
                                </td>
                            @endforeach
                            <td>@if ($item->isGlobal())<span class="badge text-bg-info">Global</span>@else<span class="badge text-bg-light">Institución</span>@endif</td>
                            <td>
                                @if ($item->is_active)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Inactivo</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($platform ? $item->isGlobal() : $item->isOwnedBy($institutionId))
                                <a href="{{ route($prefix.'.edit', [$def['key'], $item->id]) }}" class="btn btn-sm btn-outline-primary">Editar<span class="visually-hidden"> {{ $item->name }}</span></a>
                                <form method="POST" action="{{ route($prefix.'.toggle', [$def['key'], $item->id]) }}" class="d-inline"
                                      data-confirm="{{ $item->is_active ? 'Dejará de aparecer en los formularios. Los casos que ya lo usan no cambian.' : 'El registro volverá a aparecer en los formularios.' }}"
                                      data-confirm-title="{{ $item->is_active ? 'Desactivar registro' : 'Activar registro' }}" data-confirm-ok="{{ $item->is_active ? 'Desactivar' : 'Activar' }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                        {{ $item->is_active ? 'Desactivar' : 'Activar' }}<span class="visually-hidden"> {{ $item->name }}</span>
                                    </button>
                                </form>
                                @else
                                    <span class="small text-secondary"><i class="bi bi-lock" aria-hidden="true"></i> Solo lectura</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($def['columns']) + 3 }}"><x-empty icon="{{ $def['icon'] }}" title="No hay registros" text="Cree el primero con «Nuevo registro»." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())<div class="card-body border-top">{{ $items->links() }}</div>@endif
    </div>
</x-layouts.app>
