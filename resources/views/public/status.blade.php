<x-layouts.public title="Consultar un reporte">
<div class="container py-5" style="max-width:640px">
    <nav aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ $institution->publicRoute('home') }}">Inicio</a></li><li class="breadcrumb-item active" aria-current="page">Consultar un reporte</li></ol></nav>
    <h1 class="mb-2">Consultar un reporte</h1>
    <p class="text-secondary mb-4">Escribe el código y la clave que recibiste al enviar tu reporte. Solo verás el estado general, nunca el contenido del caso.</p>

    @if ($result)
        <div class="form-panel mb-4" role="status">
            <p class="eyebrow mb-1">Reporte {{ $result['code'] }}</p>
            <div class="d-flex align-items-center gap-2 mb-3"><x-status-badge :status="$result['status']" /></div>
            <p class="mb-3">{{ $result['message'] }}</p>
            <dl class="row kv mb-0 small">
                <dt class="col-sm-5">Recibido</dt><dd class="col-sm-7 tabular">{{ $result['received_at']->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-5">Última actualización</dt><dd class="col-sm-7 tabular">{{ $result['updated_at']->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
    @elseif (! empty($notFound))
        <div class="alert alert-warning" role="alert">No encontramos un reporte con esos datos. Revisa el código y la clave. Si los perdiste, acércate al DECE.</div>
    @endif

    <form method="POST" action="{{ $institution->publicRoute('status.lookup') }}" class="form-panel" novalidate>
        @csrf
        <x-form.input name="code" label="Código del reporte" required maxlength="30" autocomplete="off" placeholder="Ej.: UECRC-2026-000123" />
        <x-form.input name="key" label="Clave de consulta" required maxlength="20" autocomplete="off" placeholder="XXXX-XXXX" />
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search" aria-hidden="true"></i>Consultar</button>
    </form>
    <p class="small text-secondary mt-3">Por seguridad, se limita el número de consultas por minuto.</p>
</div>
</x-layouts.public>
