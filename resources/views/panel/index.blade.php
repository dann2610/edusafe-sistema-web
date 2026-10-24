<x-layouts.app title="Inicio" heading="Hola, {{ $user->name }}"
                lead="{{ $user->role?->name }}{{ $user->institution ? ' · '.$user->institution->name : '' }}{{ $user->externalEntity ? ' · '.$user->externalEntity->name : '' }}">
    @if ($quick)
        <x-slot:actions>
            @foreach (array_slice($quick, 0, 3) as [$label, $icon, $url, $class])
                <a href="{{ $url }}" class="btn {{ $class }}"><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $label }}</a>
            @endforeach
        </x-slot:actions>
    @endif

    @foreach ($groups as $title => $cards)
        <h2 class="panel-group-title">{{ $title }}</h2>
        <div class="row g-3 mb-2">
            @foreach ($cards as [$label, $value, $url, $accent])
                <div class="col-6 col-md-4 col-xl">
                    <a href="{{ $url }}" class="card stat-card h-100 text-decoration-none" style="--accent: {{ $accent }}">
                        <div class="card-body"><span class="stat-accent" aria-hidden="true"></span>
                            <div class="stat-value">{{ number_format($value) }}</div><div class="stat-label">{{ $label }}</div></div>
                    </a>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="row g-4 mt-1">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-bell" aria-hidden="true"></i>Notificaciones recientes
                    @if ($unread)<span class="badge text-bg-danger ms-auto">{{ $unread }} sin leer</span>@endif</div>
                @if ($recent->isEmpty())
                    <x-empty icon="bi-bell-slash" title="Sin notificaciones" text="Aquí verá asignaciones, cambios de estado y recordatorios." />
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($recent as $n)
                            <li @class(['list-group-item py-3', 'notification-unread' => ! $n->read_at])>
                                <a href="{{ route('notifications.open', $n->id) }}" class="@if (! $n->read_at) fw-semibold @endif text-body">{{ $n->data['mensaje'] ?? 'Notificación' }}</a>
                                <div class="small text-secondary">{{ $n->created_at->diffForHumans() }}</div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="card-footer"><a href="{{ route('notifications.index') }}">Ver todas</a></div>
                @endif
            </div>
        </div>

        <div class="col-lg-5 d-grid gap-4 align-content-start">
            <div class="card">
                <div class="card-header"><i class="bi bi-person-circle" aria-hidden="true"></i>Su cuenta</div>
                <div class="card-body">
                    <dl class="row kv mb-0 small">
                        <dt class="col-5">Correo</dt><dd class="col-7 text-break">{{ $user->email }}</dd>
                        <dt class="col-5">Cargo</dt><dd class="col-7">{{ $user->position ?? '—' }}</dd>
                        <dt class="col-5 mb-0">Último ingreso</dt><dd class="col-7 mb-0">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
            <div class="note">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <div><strong class="text-body d-block mb-1">Uso responsable</strong>La información de este sistema involucra a niñas, niños y adolescentes. Consulte solo lo necesario para sus funciones y no comparta capturas ni datos fuera de los canales institucionales.</div>
            </div>
        </div>
    </div>
</x-layouts.app>
