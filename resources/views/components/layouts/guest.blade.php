@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('seguridad_escolar.sistema.nombre') }}</title>
    <link rel="preload" href="{{ asset('vendor/inter/inter-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="auth-wrapper" id="contenido">
    <div class="w-100" style="max-width:440px">
        <div class="text-center mb-4">
            <span class="app-brand-mark mb-3" style="width:56px;height:56px;font-size:1.7rem;border-radius:16px" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <div class="auth-institution">Acceso del personal autorizado</div>
            <div class="fw-bold" style="color:var(--c-ink)">{{ config('seguridad_escolar.sistema.nombre') }}</div>
            <div class="small text-secondary">{{ config('seguridad_escolar.sistema.subtitulo') }}</div>
        </div>

        <div class="card auth-card">
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>

        <p class="emergency-note mt-4 mb-0">
            <i class="bi bi-telephone-fill text-danger me-1" aria-hidden="true"></i>
            <strong>¿Hay un riesgo inmediato?</strong> Llame al <strong>ECU 911</strong>. Este sistema no atiende emergencias en tiempo real.
        </p>
    </div>
</main>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
