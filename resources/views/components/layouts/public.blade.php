@props(['title' => null, 'narrow' => false])
@php
    /** @var \App\Models\Institution|null $inst */
    $inst = $publicInstitution ?? null;
    $instName = $inst?->name ?? config('seguridad_escolar.sistema.nombre');
    $modules = app(\App\Services\ModuleService::class);
    $canReport = $inst && $modules->enabled('reporte_publico');
    $canLookup = $inst && $modules->enabled('consulta_estado');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Canal institucional de prevención y reporte de situaciones que afectan la convivencia y la seguridad escolar · {{ $instName }}.">
    <meta name="robots" content="noindex">
    <title>{{ $title ? $title.' · ' : '' }}{{ $instName }}</title>
    <link rel="preload" href="{{ asset('vendor/inter/inter-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="public-body">
<a href="#contenido" class="skip-link">Saltar al contenido</a>

<div class="emergency-bar py-2 px-3">
    <div class="container d-flex flex-wrap align-items-center justify-content-center gap-2 text-center">
        <i class="bi bi-telephone-fill" aria-hidden="true"></i>
        <span><strong>¿Alguien está en peligro ahora?</strong> Llame al <a href="tel:911">ECU 911</a>. Este canal no atiende emergencias en tiempo real.</span>
    </div>
</div>

<header class="public-header">
    <div class="container d-flex align-items-center py-2 gap-3" style="min-height:64px">
        <a href="{{ $inst ? $inst->publicRoute('home') : url('/') }}" class="app-brand d-flex align-items-center gap-2 me-auto">
            <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <span>{{ $instName }}<small>Convivencia segura y prevención</small></span>
        </a>
        <nav aria-label="Navegación pública" class="d-none d-md-flex align-items-center gap-1">
            @if ($canLookup)
                <a href="{{ $inst->publicRoute('status') }}" class="btn btn-link btn-sm text-decoration-none">Consultar un reporte</a>
            @endif
            @if ($inst)
                <a href="{{ $inst->publicRoute('privacy') }}" class="btn btn-link btn-sm text-decoration-none">Privacidad</a>
            @endif
        </nav>
        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm text-nowrap"><i class="bi bi-person" aria-hidden="true"></i><span>Personal</span></a>
    </div>
</header>

<main id="contenido" tabindex="-1">
    {{ $slot }}
</main>

<footer class="public-footer py-4 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="fw-semibold text-body mb-1">{{ $instName }}</div>
                @if ($inst?->location())<div>{{ $inst->location() }}</div>@endif
                <div class="mt-2">{{ config('seguridad_escolar.sistema.nombre') }} — {{ config('seguridad_escolar.sistema.subtitulo') }}.</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-semibold text-body mb-1">Este canal</div>
                <ul class="list-unstyled mb-0 d-grid gap-1">
                    @if ($canReport)<li><a href="{{ $inst->publicRoute('report') }}">Reportar un incidente</a></li>@endif
                    @if ($canLookup)<li><a href="{{ $inst->publicRoute('status') }}">Consultar un reporte</a></li>@endif
                    @if ($inst)<li><a href="{{ $inst->publicRoute('privacy') }}">Aviso de privacidad</a></li>@endif
                    <li><a href="{{ route('public.directory') }}">Otras instituciones</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <div class="fw-semibold text-body mb-1">Emergencias</div>
                <ul class="list-unstyled mb-0 d-grid gap-1">
                    @foreach (config('seguridad_escolar.emergencias') as $e)
                        <li>{{ $e['nombre'] }}: <a href="tel:{{ $e['contacto'] }}" class="fw-semibold">{{ $e['contacto'] }}</a></li>
                    @endforeach
                </ul>
                <p class="mt-2 mb-0">Este sistema no está conectado con la Policía, el ECU 911 ni el Ministerio de Educación.</p>
            </div>
        </div>
    </div>
</footer>
<div id="confirm-dialog-root"></div>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
