<x-layouts.guest title="Iniciar sesión">
    <h1 class="auth-title mb-1">Iniciar sesión</h1>
    <p class="text-secondary small mb-4">Acceso para personal autorizado de la institución.</p>

    <x-flash />

    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Correo institucional" required
                      autocomplete="username" autofocus maxlength="255" />
        <x-form.input name="password" type="password" label="Contraseña" required
                      autocomplete="current-password" maxlength="255" />

        <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Ingresar
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('password.request') }}" class="small">¿Olvidó su contraseña?</a>
    </div>
</x-layouts.guest>
