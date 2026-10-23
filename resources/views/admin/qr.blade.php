<x-layouts.app title="Código QR" heading="Código QR institucional"
                lead="Lleva a la página pública de la institución. No contiene datos personales ni contraseñas."
                :breadcrumbs="['Código QR' => null]">
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card text-center h-100">
                <div class="card-body">
                    @if ($url)
                        <div class="d-inline-block p-3 bg-white border rounded mb-3" style="max-width:320px">{!! $svg !!}</div>
                        <p class="small text-break mb-3"><strong>Destino:</strong> <a href="{{ $url }}" target="_blank" rel="noopener">{{ $url }}</a></p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('admin.qr.download', 'png') }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1" aria-hidden="true"></i>PNG</a>
                            <a href="{{ route('admin.qr.download', 'svg') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-1" aria-hidden="true"></i>SVG (para imprenta)</a>
                        </div>
                    @else
                        <i class="bi bi-qr-code text-secondary d-block mb-2" style="font-size:4rem" aria-hidden="true"></i>
                        <p class="fw-semibold mb-1">El QR aún no puede generarse</p>
                        <p class="small text-secondary mb-0">Configure en el archivo <code>.env</code> la variable <code>QR_URL_PUBLICA</code> con la dirección pública del sistema.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            @foreach ($warnings as $w)
                <div class="alert alert-warning small d-flex gap-2" role="alert"><i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i><div>{{ $w }}</div></div>
            @endforeach
            <div class="card">
                <div class="card-header">Configuración actual</div>
                <div class="card-body small">
                    <dl class="row kv mb-3">
                        <dt class="col-sm-4">QR_URL_PUBLICA</dt><dd class="col-sm-8 text-break">{{ $configured ?: '(vacía)' }}</dd>
                        <dt class="col-sm-4">QR_DESTINO</dt><dd class="col-sm-8">{{ $destination === 'formulario' ? 'formulario (abre directamente el reporte)' : 'bienvenida (página pública)' }}</dd>
                    </dl>
                    <div class="fw-semibold mb-1">Cómo actualizar el QR</div>
                    <ol class="mb-3 ps-3">
                        <li>Publique el sistema en un servidor con dominio y certificado HTTPS.</li>
                        <li>En el archivo <code>.env</code> del servidor defina <code>QR_URL_PUBLICA=https://su-dominio</code>.</li>
                        <li>Ejecute <code>php artisan config:clear</code> (o <code>config:cache</code> en producción).</li>
                        <li>Vuelva a esta página, verifique el destino escaneándolo con un teléfono y descargue el QR.</li>
                        <li>Si la dirección cambia, repita los pasos y reemplace el QR impreso.</li>
                    </ol>
                    <p class="text-secondary mb-0">Este QR es propio de la institución. No altere ni reutilice códigos QR oficiales de otras entidades, como la Policía Nacional.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
