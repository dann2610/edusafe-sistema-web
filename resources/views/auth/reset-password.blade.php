<x-layouts.guest title="Restablecer contraseña">
    <h1 class="auth-title mb-1">Nueva contraseña</h1>
    <p class="text-secondary small mb-4">Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.</p>

    <x-flash />

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Correo institucional" :value="$email" required autocomplete="username" maxlength="255" />
        <x-form.input name="password" type="password" label="Nueva contraseña" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirmar nueva contraseña" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary w-100 py-2">Guardar contraseña</button>
    </form>
</x-layouts.guest>
