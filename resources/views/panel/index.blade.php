<x-layouts.app title="Inicio" heading="Hola, {{ $user->name }}"
                lead="{{ $user->role?->name }}{{ $user->externalEntity ? ' · '.$user->externalEntity->name : '' }}">
    @if ($quick)
        <x-slot:actions>
            @foreach (array_slice($quick, 0, 3) as [$label, $icon, $url, $class])
                <a href="{{ $url }}" class="btn {{ $class }}"><i class="bi {{ $icon }} me-1" aria-hidden="true"></i>{{ $label }}</a>
            @endforeach
        </x-slot:actions>
    @endif

    @if ($cards)
        <div class="row g-3 mb-4">
            @foreach ($cards as [$label, $value, $url, $color])
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ $url }}" class="card stat-card h-100 text-decoration-none" style="border-left-color:{{ $color }}">
                        <div class="card-body"><div class="stat-value">{{ $value }}</div><div class="stat-label">{{ $label }}</div></div>
                    </a>
                </div>
            @endforeach
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
                                <li class="d-flex gap-2 py-1"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i>{{ $function }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
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
