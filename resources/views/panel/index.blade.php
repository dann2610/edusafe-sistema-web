<x-layouts.app title="Inicio" heading="Hola, {{ $user->name }}"
                lead="{{ $user->role?->name }}{{ $user->externalEntity ? ' · '.$user->externalEntity->name : '' }}">

    @if ($stats)
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card stat-card h-100"><div class="card-body">
                    <div class="stat-value">{{ $stats['activos'] }}</div>
                    <div class="stat-label">Cuentas activas</div>
                </div></div>
            </div>
            <div class="col-sm-4">
                <div class="card stat-card h-100" style="border-left-color:#8a96a3"><div class="card-body">
                    <div class="stat-value">{{ $stats['inactivos'] }}</div>
                    <div class="stat-label">Cuentas desactivadas</div>
                </div></div>
            </div>
            <div class="col-sm-4">
                <div class="card stat-card h-100" style="border-left-color:#c98a04"><div class="card-body">
                    <div class="stat-value">{{ $stats['pendientes'] }}</div>
                    <div class="stat-label">Con contraseña temporal pendiente</div>
                </div></div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-ui-checks-grid me-2" aria-hidden="true"></i>Funciones habilitadas para su perfil</div>
                <div class="card-body">
                    @if ($functions->isEmpty())
                        <p class="text-secondary mb-0">Su perfil no tiene funciones asignadas.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($functions as $function)
                                <li class="d-flex gap-2 py-1">
                                    <i class="bi bi-check2-circle text-success" aria-hidden="true"></i>{{ $function }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @can('usuarios.gestionar')
                    <div class="card-footer bg-white">
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-people me-1" aria-hidden="true"></i>Gestionar usuarios
                        </a>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person-circle me-2" aria-hidden="true"></i>Su cuenta</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary fw-normal">Correo</dt><dd class="col-7 text-break">{{ $user->email }}</dd>
                        <dt class="col-5 text-secondary fw-normal">Cargo</dt><dd class="col-7">{{ $user->position ?? '—' }}</dd>
                        <dt class="col-5 text-secondary fw-normal mb-0">Último ingreso</dt>
                        <dd class="col-7 mb-0">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="card border-0" style="background:var(--tic-teal-soft)">
                <div class="card-body small">
                    <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Uso responsable</div>
                    La información de este sistema involucra a niñas, niños y adolescentes. Consulte solo lo necesario para
                    sus funciones y no comparta capturas ni datos fuera de los canales institucionales.
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
