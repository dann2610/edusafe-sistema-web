<x-layouts.app title="Usuarios" heading="Usuarios"
                lead="Cuentas del personal autorizado y sus roles."
                :breadcrumbs="['Usuarios' => null]">
    <x-slot:actions>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nuevo usuario
        </a>
    </x-slot:actions>

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.usuarios.index') }}" class="row g-2 align-items-end" role="search">
                <div class="col-md-5">
                    <label for="q" class="form-label small mb-1">Buscar</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                           placeholder="Nombre o correo" maxlength="100">
                </div>
                <div class="col-6 col-md-3">
                    <label for="rol" class="form-label small mb-1">Rol</label>
                    <select id="rol" name="rol" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(($filters['rol'] ?? '') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="estado" class="form-label small mb-1">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activos" @selected(($filters['estado'] ?? '') === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filters['estado'] ?? '') === 'inactivos')>Desactivados</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtrar</button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary" aria-label="Limpiar filtros"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Listado de usuarios</caption>
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col" class="d-none d-md-table-cell">Correo</th>
                        <th scope="col" class="d-none d-sm-table-cell">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="d-none d-lg-table-cell">Último ingreso</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $u->name }}</div>
                                <div class="small text-secondary d-md-none text-break">{{ $u->email }}</div>
                                <div class="small d-sm-none">{{ $u->role?->name ?? 'Sin rol' }}</div>
                                @if ($u->position)<div class="small text-secondary d-none d-sm-block">{{ $u->position }}</div>@endif
                            </td>
                            <td class="d-none d-md-table-cell text-break">{{ $u->email }}</td>
                            <td class="d-none d-sm-table-cell">
                                {{ $u->role?->name ?? 'Sin rol' }}
                                @if ($u->externalEntity)<div class="small text-secondary">{{ $u->externalEntity->name }}</div>@endif
                            </td>
                            <td>
                                @if ($u->is_active)
                                    <span class="badge text-bg-success">Activa</span>
                                    @if ($u->must_change_password)<span class="badge text-bg-warning">Clave temporal</span>@endif
                                @else
                                    <span class="badge text-bg-secondary">Desactivada</span>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell small">{{ $u->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.usuarios.show', $u) }}" class="btn btn-sm btn-outline-primary">
                                    Ver<span class="visually-hidden"> {{ $u->name }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-5">No se encontraron usuarios con esos criterios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-body border-top">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
