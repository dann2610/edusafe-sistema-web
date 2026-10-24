{{-- Datos complementarios del reporte (nuevos campos y campos adicionales de la categoría). --}}
<dt class="col-sm-4">¿Continúa?</dt><dd class="col-sm-8">{{ \App\Models\Incident::yesNoLabel($incident->is_ongoing) }}</dd>
<dt class="col-sm-4">Informado a autoridad</dt>
<dd class="col-sm-8">{{ \App\Models\Incident::yesNoLabel($incident->authority_informed) }}@if ($incident->authority_informed_detail) — {{ $incident->authority_informed_detail }}@endif</dd>
@foreach ($incident->extra_data ?? [] as $item)
    <dt class="col-sm-4">{{ $item['label'] ?? $item['key'] }}</dt>
    <dd class="col-sm-8">
        @if (($item['type'] ?? '') === 'casilla'){{ $item['value'] ? 'Sí' : 'No' }}
        @elseif (($item['type'] ?? '') === 'fecha'){{ \Illuminate\Support\Carbon::parse($item['value'])->format('d/m/Y') }}
        @else{{ $item['value'] }}@endif
    </dd>
@endforeach
