<x-layouts.app :title="$incident->code" :heading="'Reporte '.$incident->code" :lead="$incident->category->name"
                :breadcrumbs="['Mis reportes' => route('reportes.mine'), $incident->code => null]">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">
                    <span class="me-auto">Datos registrados</span><x-status-badge :status="$incident->status" />
                </div>
                <div class="card-body">
                    <dl class="row kv mb-3">
                        <dt class="col-sm-4">Registrado</dt><dd class="col-sm-8">{{ $incident->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-4">Fecha del hecho</dt><dd class="col-sm-8">{{ $incident->occurredLabel() }} <span class="small text-secondary">(aproximada)</span></dd>
                        <dt class="col-sm-4">Lugar</dt><dd class="col-sm-8">{{ $incident->location ?? '—' }}</dd>
                        <dt class="col-sm-4">Curso</dt><dd class="col-sm-8">{{ $incident->course?->display_name ?? '—' }}</dd>
                        <dt class="col-sm-4">Riesgo inmediato</dt><dd class="col-sm-8">{{ $incident->immediate_risk ? 'Sí' : 'No' }}</dd>
                        @include('cases.partials._report-details')
                    </dl>
                    <div class="fw-semibold small mb-1">Descripción</div>
                    <p class="description-text mb-0">{{ $incident->description }}</p>
                </div>
            </div>
            @include('cases.partials.notes', ['noteForm' => auth()->user()->can('addNote', $incident)])
            @if ($incident->participants->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header">Personas involucradas registradas</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($incident->participants as $p)
                            <li class="list-group-item small">{{ $p->student?->full_name ?? $p->non_student_description }} — {{ $p->role->label() }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @php $mine = $incident->attachments->where('uploaded_by', auth()->id()); @endphp
            @if ($mine->isNotEmpty())
                <div class="card">
                    <div class="card-header">Evidencias adjuntadas por usted</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($mine as $a)
                            <li class="list-group-item small d-flex justify-content-between">
                                <a href="{{ route('attachments.download', $a) }}"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>{{ $a->original_name }}</a>
                                <span class="text-secondary">{{ $a->humanSize() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Estado de atención</div>
                <div class="card-body">
                    <ul class="timeline">
                        @foreach ($statusHistory as $h)
                            <li>
                                <div class="fw-semibold small">{{ $h->to_status->label() }}</div>
                                <div class="small text-secondary">{{ $h->created_at->format('d/m/Y H:i') }}</div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="small text-secondary mb-0 mt-2">
                        Por confidencialidad, las actuaciones, medidas y derivaciones solo las consultan el DECE y los responsables asignados.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
