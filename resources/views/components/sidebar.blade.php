@props(['user'])
@php
    // Cada entrada se muestra solo si el usuario tiene el permiso correspondiente.
    // Los módulos se agregan aquí a medida que se implementan en cada sprint.
    $sections = [
        'General' => [
            ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'panel', 'active' => 'panel', 'can' => null],
            ['label' => 'Mi perfil', 'icon' => 'bi-person-badge', 'route' => 'profile.show', 'active' => 'profile.*', 'can' => null],
        ],
        'Administración' => [
            ['label' => 'Usuarios', 'icon' => 'bi-people', 'route' => 'admin.usuarios.index', 'active' => 'admin.usuarios.*', 'can' => 'usuarios.gestionar'],
        ],
    ];
@endphp
<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menú principal">
    <div class="offcanvas-header d-lg-none">
        <span class="fw-semibold text-white">Menú</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar menú"></button>
    </div>
    <div class="offcanvas-body d-block p-0">
        <nav>
            @foreach ($sections as $section => $items)
                @php $visible = array_filter($items, fn ($i) => $i['can'] === null || $user->can($i['can'])); @endphp
                @if ($visible)
                    <p class="nav-section">{{ $section }}</p>
                    <ul class="nav flex-column">
                        @foreach ($visible as $item)
                            @php $isActive = request()->routeIs($item['active']); @endphp
                            <li class="nav-item">
                                <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }}" @if ($isActive) aria-current="page" @endif>
                                    <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        </nav>

        <div class="sidebar-note">
            <strong><i class="bi bi-telephone-fill me-1" aria-hidden="true"></i>Emergencias: ECU 911</strong><br>
            Este sistema no atiende emergencias en tiempo real.
        </div>
    </div>
</aside>
