<x-layouts.app title="Cambiar contraseña" heading="Cambiar contraseña"
                lead="Al cambiarla se cerrarán sus sesiones abiertas en otros equipos."
                :breadcrumbs="['Cambiar contraseña' => null]">
    <div class="row">
        <div class="col-lg-7 col-xl-5">
            @if ($forced)
                <div class="alert alert-warning d-flex gap-2" role="alert">
                    <i class="bi bi-shield-lock-fill mt-1" aria-hidden="true"></i>
                    <div>Su cuenta tiene una contraseña temporal. Debe crear una contraseña personal para continuar.</div>
                </div>
            @endif

            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('password.change.update') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <x-form.input name="current_password" type="password" label="Contraseña actual" required autocomplete="current-password" />
                        <x-form.input name="password" type="password" label="Nueva contraseña" required autocomplete="new-password"
                                      help="Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos." />
                        <x-form.input name="password_confirmation" type="password" label="Confirmar nueva contraseña" required autocomplete="new-password" />
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Guardar contraseña</button>
                            @unless ($forced)
                                <a href="{{ route('panel') }}" class="btn btn-outline-secondary">Cancelar</a>
                            @endunless
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
