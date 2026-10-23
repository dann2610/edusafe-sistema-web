<x-layouts.app title="{{ $user->name }}" :heading="$user->name" :lead="$user->email"
                :breadcrumbs="['Usuarios' => route('admin.usuarios.index'), $user->name => null]">
    <x-slot:actions>
        @can('update', $user)
            <a href="{{ route('admin.usuarios.edit', $user) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Editar</a>
        @endcan
    </x-slot:actions>

    @if (session('temporary_password'))
        <div class="alert alert-info" role="status">
            <div class="fw-semibold mb-2"><i class="bi bi-key-fill me-1" aria-hidden="true"></i>Contraseña temporal</div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="temp-password">{{ session('temporary_password') }}</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-copy="{{ session('temporary_password') }}">
                    <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copiar
                </button>
            </div>
            <div class="small">
                Entréguela a la persona por un medio seguro. <strong>No se volverá a mostrar.</strong>
                Deberá cambiarla en su primer ingreso.
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Datos de la cuenta</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">Cargo</dt><dd class="col-sm-8">{{ $user->position ?? '—' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Rol</dt><dd class="col-sm-8">{{ $user->role?->name ?? 'Sin rol' }}</dd>
                        @if ($user->externalEntity)
                            <dt class="col-sm-4 text-secondary fw-normal">Entidad</dt><dd class="col-sm-8">{{ $user->externalEntity->name }}</dd>
                        @endif
                        <dt class="col-sm-4 text-secondary fw-normal">Estado</dt>
                        <dd class="col-sm-8">
                            @if ($user->is_active)
                                <span class="badge text-bg-success">Activa</span>
                                @if ($user->must_change_password)<span class="badge text-bg-warning">Contraseña temporal pendiente</span>@endif
                            @else
                                <span class="badge text-bg-secondary">Desactivada</span>
                                <span class="small text-secondary">desde {{ $user->deactivated_at?->format('d/m/Y H:i') }}</span>
                            @endif
                        </dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Último ingreso</dt><dd class="col-sm-8">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal mb-0">Creada</dt><dd class="col-sm-8 mb-0">{{ $user->created_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Actividad reciente</div>
                @if ($activity->isEmpty())
                    <div class="card-body text-secondary small">Sin registros en la bitácora.</div>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach ($activity as $log)
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span>
                                    {{ str_replace('_', ' ', ucfirst($log->action)) }}
                                    @if ($log->result->value !== 'exito')
                                        <span class="badge {{ $log->result->value === 'denegado' ? 'text-bg-warning' : 'text-bg-danger' }}">{{ $log->result->label() }}</span>
                                    @endif
                                </span>
                                <span class="text-secondary text-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Acciones de seguridad</div>
                <div class="card-body">
                    @can('resetPassword', $user)
                        <form method="POST" action="{{ route('admin.usuarios.reset-password', $user) }}" class="mb-4"
                              data-confirm="Se generará una nueva contraseña temporal y se cerrarán las sesiones abiertas de esta persona. ¿Continuar?">
                            @csrf
                            <p class="small text-secondary mb-2">Genera una nueva contraseña temporal y cierra las sesiones abiertas.</p>
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Generar contraseña temporal</button>
                        </form>
                    @endcan

                    @if ($user->is_active)
                        @can('deactivate', $user)
                            <form method="POST" action="{{ route('admin.usuarios.deactivate', $user) }}"
                                  data-confirm="La persona no podrá ingresar y se cerrarán sus sesiones. ¿Desactivar la cuenta?">
                                @csrf
                                @method('PATCH')
                                <x-form.input name="reason" label="Motivo de la desactivación" required maxlength="200"
                                              help="Queda registrado en la bitácora. Ejemplo: fin de contrato." />
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-person-x me-1" aria-hidden="true"></i>Desactivar cuenta</button>
                            </form>
                        @else
                            <p class="small text-secondary mb-0">
                                <i class="bi bi-lock me-1" aria-hidden="true"></i>
                                @if (auth()->user()->is($user))
                                    No puede desactivar su propia cuenta.
                                @else
                                    Es el último administrador técnico activo y no puede desactivarse.
                                @endif
                            </p>
                        @endcan
                    @else
                        @can('activate', $user)
                            <form method="POST" action="{{ route('admin.usuarios.activate', $user) }}">
                                @csrf
                                @method('PATCH')
                                <p class="small text-secondary mb-2">La persona podrá volver a ingresar con su contraseña actual.</p>
                                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-person-check me-1" aria-hidden="true"></i>Activar cuenta</button>
                            </form>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
