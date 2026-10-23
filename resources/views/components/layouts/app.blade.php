@props(['title' => null, 'heading' => null, 'lead' => null, 'breadcrumbs' => []])
@php
    $user = auth()->user()->loadMissing('role');
    $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $systemName = config('seguridad_escolar.sistema.nombre');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ $systemName }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<a href="#contenido" class="skip-link">Saltar al contenido</a>

<header class="app-topbar d-flex align-items-center px-3 px-lg-4">
    <button class="btn btn-link text-body d-lg-none me-2 p-1" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menú">
        <i class="bi bi-list fs-3" aria-hidden="true"></i>
    </button>

    <a href="{{ route('panel') }}" class="app-brand d-flex align-items-center gap-2">
        <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
        <span class="d-none d-sm-inline">
            {{ $systemName }}
            <small>{{ config('seguridad_escolar.institucion.nombre') }}</small>
        </span>
        <span class="d-sm-none">TIC · UES</span>
    </a>

    <div class="ms-auto dropdown">
        <button class="btn btn-link text-body text-decoration-none d-flex align-items-center gap-2 p-1"
                type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de la cuenta">
            <span class="user-avatar" aria-hidden="true">{{ $initials }}</span>
            <span class="d-none d-md-inline text-start lh-sm">
                <span class="d-block fw-semibold small">{{ $user->name }}</span>
                <span class="d-block text-secondary" style="font-size:.75rem">{{ $user->role?->name }}</span>
            </span>
            <i class="bi bi-chevron-down small" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li class="px-3 py-2 d-md-none">
                <div class="fw-semibold small">{{ $user->name }}</div>
                <div class="text-secondary small">{{ $user->role?->name }}</div>
            </li>
            <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>Mi perfil</a></li>
            <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key me-2" aria-hidden="true"></i>Cambiar contraseña</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Cerrar sesión</button>
                </form>
            </li>
        </ul>
    </div>
</header>

<div class="d-lg-flex">
    <x-sidebar :user="$user" />

    <main id="contenido" class="app-main flex-grow-1 p-3 p-lg-4" tabindex="-1">
        @if ($heading)
            <div class="page-header mb-4">
                @if ($breadcrumbs)
                    <nav aria-label="Ruta de navegación">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
                            @foreach ($breadcrumbs as $label => $url)
                                @if ($url)
                                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                                @else
                                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                @endif
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h1>{{ $heading }}</h1>
                        @if ($lead)<p class="lead-text">{{ $lead }}</p>@endif
                    </div>
                    @isset($actions)
                        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
                    @endisset
                </div>
            </div>
        @endif

        <x-flash />

        {{ $slot }}

        <footer class="app-footer mt-5 pt-3 border-top">
            Herramienta de apoyo institucional. No reemplaza los protocolos oficiales ni los canales de emergencia:
            ante un riesgo inmediato llame al <strong>ECU 911</strong>.
        </footer>
    </main>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
