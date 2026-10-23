<div class="card">
    <div class="card-body">
        <p class="small text-secondary">Cronología completa del caso. Estos registros no pueden modificarse ni eliminarse.</p>
        <ul class="timeline">
            @foreach ($incident->events as $e)
                <li class="ev-{{ $e->event_type->value }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <span class="fw-semibold small">{{ $e->event_type->label() }}</span>
                        <span class="small text-secondary">{{ $e->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="small">{{ $e->summary }}</div>
                    <div class="small text-secondary">{{ $e->user?->name ?? 'Formulario público' }}</div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
