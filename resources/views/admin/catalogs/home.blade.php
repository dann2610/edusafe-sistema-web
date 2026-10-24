<x-layouts.app title="Catálogos" heading="Catálogos institucionales"
                lead="Datos de referencia que usan los formularios de su institución. Los registros globales los define la plataforma."
                :breadcrumbs="['Catálogos' => null]">
    @php
        $cards = [
            ['Categorías de incidentes', 'bi-tags', route('admin.categories.index'), 'Tipos de situación, prioridad inicial y campos adicionales.'],
            ['Periodos lectivos', 'bi-calendar3', route('admin.periodos.index'), 'Años lectivos y periodo vigente.'],
            ['Cursos', 'bi-easel', route('admin.cursos.index'), 'Cursos, paralelos, jornadas y tutores.'],
            ['Estudiantes', 'bi-mortarboard', route('admin.estudiantes.index'), 'Código, nombres y matrícula del periodo vigente.'],
        ];
        foreach (\App\Support\CatalogRegistry::all() as $key => $def) {
            $cards[] = [$def['title'], $def['icon'], route('admin.catalogos.index', $key), null];
        }
    @endphp
    <div class="row g-3">
        @foreach ($cards as [$title, $icon, $url, $desc])
            <div class="col-sm-6 col-xl-4">
                <a href="{{ $url }}" class="card h-100 text-decoration-none text-body catalog-card">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="app-brand-mark" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                        <span>
                            <span class="d-block fw-semibold" style="color:var(--c-ink)">{{ $title }}</span>
                            @if ($desc)<span class="d-block small text-secondary">{{ $desc }}</span>@endif
                        </span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-layouts.app>
