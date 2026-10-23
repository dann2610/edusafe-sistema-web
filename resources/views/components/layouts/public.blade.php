@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Canal institucional de prevención y reporte de situaciones de riesgo de la {{ config('seguridad_escolar.institucion.nombre') }}.">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('seguridad_escolar.institucion.nombre') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="public-body">
<a href="#contenido" class="skip-link">Saltar al contenido</a>

<div class="emergency-bar text-center small py-2 px-3">
    <i class="bi bi-telephone-fill me-1" aria-hidden="true"></i>
    <strong>¿Hay un peligro inmediato?</strong> Llame al <a href="tel:911" class="fw-bold text-white">ECU 911</a>. Este canal no atiende emergencias en tiempo real.
</div>

<header class="public-header">
    <div class="container d-flex align-items-center py-3 gap-3">
        <a href="{{ route('public.home') }}" class="app-brand d-flex align-items-center gap-2">
            <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <span>{{ config('seguridad_escolar.institucion.nombre') }}<small>{{ config('seguridad_escolar.sistema.nombre') }}</small></span>
        </a>
        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm ms-auto text-nowrap"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Iniciar sesión</a>
    </div>
</header>

<main id="contenido" tabindex="-1">
    {{ $slot }}
</main>

<footer class="public-footer py-4 mt-5">
    <div class="container small d-flex flex-wrap gap-3 justify-content-between">
        <span>{{ config('seguridad_escolar.institucion.nombre') }} · {{ config('seguridad_escolar.sistema.subtitulo') }}</span>
        <a href="{{ route('public.privacy') }}">Aviso de privacidad y tratamiento de datos</a>
    </div>
</footer>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
