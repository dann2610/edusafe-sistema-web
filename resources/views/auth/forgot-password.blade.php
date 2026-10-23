<x-layouts.guest title="Recuperar contraseña">
    <h1 class="auth-title mb-1">Recuperar contraseña</h1>
    <p class="text-secondary small mb-4">
        Escriba su correo institucional. Si corresponde a una cuenta activa, recibirá un enlace para crear una nueva contraseña.
    </p>

    <x-flash />

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Correo institucional" required autocomplete="email" autofocus maxlength="255" />
        <button type="submit" class="btn btn-primary w-100 py-2">Enviar enlace</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver a iniciar sesión</a>
    </div>
</x-layouts.guest>
