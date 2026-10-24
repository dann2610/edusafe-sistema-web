<x-layouts.public title="Reporte enviado">
<div class="container py-5" style="max-width:720px">
    <div class="form-panel">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="app-brand-mark is-lg" style="background:var(--c-success-soft);color:var(--c-success)" aria-hidden="true"><i class="bi bi-check2"></i></span>
            <div>
                <h1 class="mb-0">Recibimos tu reporte</h1>
                <p class="text-secondary mb-0">Gracias por informar. Hiciste lo correcto.</p>
            </div>
        </div>

        <dl class="row kv mb-3" role="status">
            <dt class="col-sm-4">Código del reporte</dt>
            <dd class="col-sm-8"><span class="code-display">{{ $report['code'] }}</span>
                <button type="button" class="btn btn-link btn-sm" data-copy="{{ $report['code'] }}"><i class="bi bi-clipboard" aria-hidden="true"></i>Copiar</button></dd>
            <dt class="col-sm-4">Fecha de recepción</dt>
            <dd class="col-sm-8 tabular">{{ $report['received_at'] }}</dd>
            @if (! empty($report['key']))
                <dt class="col-sm-4">Clave de consulta</dt>
                <dd class="col-sm-8"><span class="code-display">{{ $report['key'] }}</span>
                    <div class="form-text">Junto con el código, te permite consultar el estado del reporte. <strong>Solo se muestra esta vez</strong>: anótala o toma una captura de esta pantalla.</div></dd>
            @endif
        </dl>

        @if ($report['urgent'])
            <div class="alert alert-danger" role="alert">
                <strong>Indicaste que alguien está en riesgo inmediato.</strong> Si el peligro es ahora, llama al <a href="tel:911" class="alert-link">ECU 911</a>
                y avisa de inmediato a un docente o a una autoridad de la institución. Este canal no atiende emergencias en tiempo real.
            </div>
        @endif

        <h2 class="h3 mt-4">¿Qué pasa ahora?</h2>
        <ol class="d-grid gap-2 mb-4">
            <li>El Departamento de Consejería Estudiantil (DECE) revisará tu reporte en horario institucional.</li>
            <li>Si dejaste un dato de contacto, es posible que te contacten para pedir más información.</li>
            <li>Si quieres agregar algo, acércate al DECE y menciona tu código.</li>
        </ol>

        <div class="note mb-4"><i class="bi bi-info-circle" aria-hidden="true"></i>
            <div>Recibir un reporte no significa que los hechos estén verificados ni que exista una responsabilidad establecida. La institución actuará según sus protocolos.</div></div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $institution->publicRoute('home') }}" class="btn btn-outline-primary">Volver al inicio</a>
            @if (! empty($report['key']))
                <a href="{{ $institution->publicRoute('status') }}" class="btn btn-link">Consultar el estado más tarde</a>
            @endif
        </div>
    </div>
</div>
</x-layouts.public>
