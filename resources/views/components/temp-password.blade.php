@if (session('temporary_password'))
    <div class="alert alert-warning" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-key me-1" aria-hidden="true"></i>Contraseña temporal{{ session('temporary_user') ? ' de '.session('temporary_user') : '' }}</div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="temp-password">{{ session('temporary_password') }}</span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-copy="{{ session('temporary_password') }}"><i class="bi bi-clipboard" aria-hidden="true"></i>Copiar</button>
        </div>
        <div class="small">Se muestra solo esta vez. Entréguela por un canal seguro: el sistema pedirá cambiarla en el primer ingreso.</div>
    </div>
@endif
