<x-layouts.public title="Reporte enviado">
    <div class="container py-5" style="max-width:640px">
        <div class="card text-center shadow-sm">
            <div class="card-body p-4 p-md-5">
                <i class="bi bi-check-circle-fill text-success d-block mb-3" style="font-size:3rem" aria-hidden="true"></i>
                <h1 class="h3 fw-bold mb-2" style="color:var(--tic-navy)">Recibimos tu reporte</h1>
                <p class="mb-4">Gracias por confiar en nosotros. El Departamento de Consejería Estudiantil lo revisará.</p>
                <p class="mb-1 small text-secondary">Código de tu reporte</p>
                <p class="temp-password d-inline-block mb-3" role="status">{{ $code }}</p>
                <p class="small text-secondary">Anótalo. Si te acercas al DECE, menciónalo para ubicar tu reporte rápidamente.</p>

                @if ($urgent)
                    <div class="alert alert-danger text-start mt-4 mb-0" role="alert">
                        <strong>Indicaste que hay un riesgo inmediato.</strong> Si el peligro es ahora, llama al <strong>ECU 911</strong>
                        y avisa de inmediato a un docente o autoridad de la institución.
                    </div>
                @endif

                <a href="{{ route('public.home') }}" class="btn btn-outline-primary mt-4">Volver al inicio</a>
            </div>
        </div>
    </div>
</x-layouts.public>
