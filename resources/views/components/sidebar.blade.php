@props(['user'])
@php
    // Cada entrada se muestra solo si el usuario tiene el permiso indicado.
    // El servidor vuelve a verificar el permiso en cada ruta.
    $hasAssignments = $user->can('seguimientos.registrar') && ! $user->can('casos.bandeja');
    $sections = [
        'General' => [
            ['label' => 'Inicio', 'icon' => 'bi-house-door', 'route' => 'panel', 'active' => 'panel', 'can' => null],
            ['label' => 'Notificaciones', 'icon' => 'bi-bell', 'route' => 'notifications.index', 'active' => 'notifications.*', 'can' => null],
        ],
        'Incidentes' => [
            ['label' => 'Registrar incidente', 'icon' => 'bi-plus-circle', 'route' => 'incidentes.create', 'active' => 'incidentes.create', 'can' => 'incidentes.registrar'],
            ['label' => 'Mis reportes', 'icon' => 'bi-journal-text', 'route' => 'reportes.mine', 'active' => 'reportes.*', 'can' => 'incidentes.ver_propios'],
        ],
        'Gestión de casos' => [
            ['label' => 'Bandeja de casos', 'icon' => 'bi-inboxes', 'route' => 'casos.inbox', 'active' => ['casos.inbox', 'casos.show'], 'can' => 'casos.bandeja'],
            ['label' => 'Casos asignados a mí', 'icon' => 'bi-person-check', 'route' => 'casos.assigned', 'active' => 'casos.assigned', 'can' => 'seguimientos.registrar'],
            ['label' => 'Casos derivados', 'icon' => 'bi-send-check', 'route' => 'entity.referrals.index', 'active' => 'entity.referrals.*', 'can' => 'derivaciones.ver_entidad'],
        ],
        'Indicadores' => [
            ['label' => 'Dashboard', 'icon' => 'bi-bar-chart-line', 'route' => 'dashboard', 'active' => 'dashboard', 'can' => 'dashboard.ver'],
            ['label' => 'Reportes', 'icon' => 'bi-file-earmark-bar-graph', 'route' => 'reports.index', 'active' => 'reports.*', 'can' => 'reportes.generar'],
        ],
        'Administración' => [
            ['label' => 'Usuarios', 'icon' => 'bi-people', 'route' => 'admin.usuarios.index', 'active' => 'admin.usuarios.*', 'can' => 'usuarios.gestionar'],
            ['label' => 'Catálogos', 'icon' => 'bi-collection', 'route' => 'admin.catalogos.home', 'active' => ['admin.catalogos.*', 'admin.periodos.*', 'admin.cursos.*', 'admin.estudiantes.*'], 'can' => 'catalogos.gestionar'],
            ['label' => 'Código QR', 'icon' => 'bi-qr-code', 'route' => 'admin.qr.show', 'active' => 'admin.qr.*', 'can' => 'configuracion.gestionar'],
            ['label' => 'Bitácora', 'icon' => 'bi-clock-history', 'route' => 'audit.index', 'active' => 'audit.*', 'can' => 'auditoria.consultar'],
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
                            @php $isActive = request()->routeIs(...(array) $item['active']); @endphp
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
