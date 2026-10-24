<x-layouts.app title="Catálogos globales" heading="Catálogos globales"
                lead="Registros disponibles para todas las instituciones. Cada institución puede agregar los suyos, pero no modificar estos."
                :breadcrumbs="['Plataforma' => route('platform.dashboard'), 'Catálogos globales' => null]">
    @php
        $cards = [['Categorías de incidentes', 'bi-tags', route('platform.categories.index'), 'Tipos de situación comunes, prioridad inicial y campos adicionales.']];
        foreach (\App\Support\CatalogRegistry::all() as $key => $def) {
            $cards[] = [$def['title'], $def['icon'], route('platform.catalogos.index', $key), null];
        }
    @endphp
    <div class="row g-3">
        @foreach ($cards as [$title, $icon, $url, $desc])
            <div class="col-sm-6 col-xl-4">
                <a href="{{ $url }}" class="card h-100 text-decoration-none text-body catalog-card">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <span class="app-brand-mark" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                        <span><span class="d-block fw-semibold" style="color:var(--c-ink)">{{ $title }}</span>
                            @if ($desc)<span class="d-block small text-secondary">{{ $desc }}</span>@endif</span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-layouts.app>
