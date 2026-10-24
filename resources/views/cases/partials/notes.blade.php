{{-- Información adicional aportada después del registro (inmutable). --}}
@if ($incident->notes->isNotEmpty() || ($noteForm ?? false))
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-chat-left-text" aria-hidden="true"></i>Información adicional</div>
    @if ($incident->notes->isEmpty())
        <div class="card-body small text-secondary">Aún no se agregó información.</div>
    @else
        <ul class="list-group list-group-flush">
            @foreach ($incident->notes->sortBy('created_at') as $note)
                <li class="list-group-item">
                    <div class="small text-secondary mb-1">{{ $note->created_at->format('d/m/Y H:i') }} · {{ $note->user?->name ?? 'Sistema' }}</div>
                    <p class="description-text mb-0">{{ $note->body }}</p>
                </li>
            @endforeach
        </ul>
    @endif
    @if ($noteForm ?? false)
        <form method="POST" action="{{ route('reportes.notes.store', $incident) }}" class="card-body border-top" @can('adjuntos.subir') enctype="multipart/form-data" @endcan novalidate>
            @csrf
            <x-form.textarea name="body" label="Agregar información para el DECE" required rows="3" maxlength="3000" help="Lo que agregue no puede editarse después; queda en el historial del caso." />
            @can('adjuntos.subir')
                <div class="mb-3"><label for="note-files" class="form-label">Archivos (opcional)</label>
                    <input type="file" id="note-files" name="attachments[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx"></div>
            @endcan
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send" aria-hidden="true"></i>Enviar información</button>
        </form>
    @endif
</div>
@endif
