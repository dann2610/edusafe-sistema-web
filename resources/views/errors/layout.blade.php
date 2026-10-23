<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · {{ config('seguridad_escolar.sistema.nombre') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="auth-wrapper">
    <div class="card auth-card text-center">
        <div class="card-body">
            <div class="display-6 fw-bold mb-2" style="color:var(--tic-teal)">@yield('code')</div>
            <h1 class="auth-title mb-2">@yield('title')</h1>
            <p class="text-secondary mb-4">@yield('message')</p>
            @auth
                <a href="{{ route('panel') }}" class="btn btn-primary"><i class="bi bi-house-door me-1" aria-hidden="true"></i>Volver al inicio</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Ir a iniciar sesión</a>
            @endauth
        </div>
    </div>
</main>
</body>
</html>
