{{-- Mensajes de resultado. role="status"/"alert" para lectores de pantalla. --}}
@foreach (['success' => ['success', 'bi-check-circle-fill', 'status'], 'status' => ['info', 'bi-info-circle-fill', 'status'], 'warning' => ['warning', 'bi-exclamation-triangle-fill', 'alert'], 'error' => ['danger', 'bi-x-octagon-fill', 'alert']] as $key => [$type, $icon, $role])
    @if (session($key))
        <div class="alert alert-{{ $type }} alert-dismissible fade show d-flex align-items-start gap-2" role="{{ $role }}">
            <i class="bi {{ $icon }} mt-1" aria-hidden="true"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar mensaje"></button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! ($hideErrorSummary ?? false))
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i>Revise los datos del formulario:</div>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
