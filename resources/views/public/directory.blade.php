<x-layouts.public title="Instituciones">
<div class="container py-5" style="max-width:820px">
    <h1 class="mb-2">Instituciones con canal de reporte</h1>
    <p class="text-secondary mb-4">Elige tu institución. Cada una recibe y atiende sus propios reportes.</p>
    @if ($institutions->isEmpty())
        <x-empty icon="bi-building" title="No hay instituciones disponibles" text="Por ahora no hay instituciones con el canal habilitado." />
    @else
        <div class="d-grid gap-2">
            @foreach ($institutions as $inst)
                <a href="{{ $inst->publicRoute('home') }}" class="card catalog-card text-decoration-none text-body">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="app-brand-mark" aria-hidden="true"><i class="bi bi-building"></i></span>
                        <span class="me-auto"><span class="fw-semibold d-block">{{ $inst->name }}</span><small class="text-secondary">{{ $inst->location() ?: 'Ecuador' }}</small></span>
                        <i class="bi bi-chevron-right text-secondary" aria-hidden="true"></i>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
</x-layouts.public>
