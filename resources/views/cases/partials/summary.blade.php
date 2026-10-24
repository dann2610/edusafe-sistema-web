<div class="row g-3">
    <div class="col-xl-7">
        <div class="card mb-3">
            <div class="card-header">Reporte</div>
            <div class="card-body">
                <dl class="row kv mb-3 small">
                    <dt class="col-sm-4">Registrado</dt><dd class="col-sm-8">{{ $incident->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-sm-4">Origen</dt>
                    <dd class="col-sm-8">
                        {{ $incident->source->label() }}
                        @if ($incident->reporter) — {{ $incident->reporter->name }} @endif
                    </dd>
                    @if ($incident->source->value === 'publico')
                        <dt class="col-sm-4">Quien reporta</dt>
                        <dd class="col-sm-8">
                            {{ $incident->reporter_relationship?->label() ?? '—' }}
                            @if ($incident->reporter_mode === 'sin_contacto')
                                <div class="text-secondary">Eligió reportar sin datos de contacto</div>
                            @elseif ($incident->reporter_contact_name || $incident->reporter_contact)
                                <div>{{ $incident->reporter_contact_name }} {{ $incident->reporter_contact ? '· '.$incident->reporter_contact : '' }}</div>
                            @else
                                <div class="text-secondary">Sin datos de contacto</div>
                            @endif
                        </dd>
                    @endif
                    <dt class="col-sm-4">Fecha del hecho</dt><dd class="col-sm-8">{{ $incident->occurredLabel() }} <span class="text-secondary">(aprox.)</span></dd>
                    <dt class="col-sm-4">Lugar</dt><dd class="col-sm-8">{{ $incident->location ?? '—' }}</dd>
                    <dt class="col-sm-4">Curso</dt><dd class="col-sm-8">{{ $incident->course?->display_name ?? '—' }}</dd>
                    <dt class="col-sm-4">Periodo</dt><dd class="col-sm-8">{{ $incident->academicPeriod?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Riesgo inmediato</dt>
                    <dd class="col-sm-8">@if ($incident->immediate_risk)<span class="badge text-bg-danger">Comunicado</span>@else No @endif</dd>
                    @include('cases.partials._report-details')
                    @if ($incident->closed_at)
                        <dt class="col-sm-4">Cerrado</dt><dd class="col-sm-8">{{ $incident->closed_at->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
                <div class="fw-semibold small mb-1">Descripción</div>
                <p class="description-text mb-0">{{ $incident->description }}</p>
                @if ($incident->closure_summary)
                    <div class="fw-semibold small mt-3 mb-1">Resumen de cierre</div>
                    <p class="description-text mb-0">{{ $incident->closure_summary }}</p>
                @endif
            </div>
        </div>

        @include('cases.partials.notes', ['noteForm' => false])

        <div class="card mb-3">
            <div class="card-header">Personas involucradas</div>
            @if ($incident->participants->isEmpty())
                <div class="card-body small text-secondary">No se registraron personas involucradas.</div>
            @else
                <ul class="list-group list-group-flush small">
                    @foreach ($incident->participants as $p)
                        <li class="list-group-item">
                            <span class="fw-semibold">{{ $p->student?->full_name ?? $p->non_student_description }}</span>
                            <span class="badge text-bg-light border ms-1">{{ $p->role->label() }}</span>
                            @if ($p->student)<span class="text-secondary"> · {{ $p->student->institutional_code }}</span>@endif
                            @if ($p->notes)<div class="text-secondary">{{ $p->notes }}</div>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($canManage && $incident->status->isOpen())
                <div class="card-body border-top">
                    <details>
                        <summary class="small fw-semibold">Agregar persona involucrada</summary>
                        <form method="POST" action="{{ route('casos.participants.store', $incident) }}" class="row g-2 mt-2" novalidate>
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label small" for="add-student">Estudiante</label>
                                <select id="add-student" name="student_id" class="form-select form-select-sm">
                                    <option value="">— No es estudiante —</option>
                                    @foreach ($studentsByCourse ?? [] as $course => $students)
                                        <optgroup label="{{ $course }}">@foreach ($students as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label small" for="add-desc">O descripción general</label><input id="add-desc" name="non_student_description" class="form-control form-control-sm" maxlength="150"></div>
                            <div class="col-md-4"><label class="form-label small" for="add-role">Rol</label>
                                <select id="add-role" name="role" class="form-select form-select-sm">@foreach ($participantRoles as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label small" for="add-notes">Observación</label><input id="add-notes" name="notes" class="form-control form-control-sm" maxlength="500"></div>
                            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100" type="submit">Agregar</button></div>
                        </form>
                    </details>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">Evidencias</div>
            @if ($incident->attachments->isEmpty())
                <div class="card-body small text-secondary">Sin evidencias adjuntas.</div>
            @else
                <ul class="list-group list-group-flush small">
                    @foreach ($incident->attachments as $a)
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <a href="{{ route('attachments.download', $a) }}" class="text-break"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>{{ $a->original_name }}</a>
                            <span class="text-secondary text-nowrap">{{ $a->humanSize() }} · {{ $a->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            @can('addAttachments', $incident)
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('casos.attachments.store', $incident) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end" novalidate>
                        @csrf
                        <div class="flex-grow-1">
                            <label for="more-files" class="form-label small">Adjuntar evidencias (PDF, JPG, PNG, DOCX)</label>
                            <input type="file" id="more-files" name="attachments[]" multiple class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.docx">
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-upload me-1" aria-hidden="true"></i>Subir</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    <div class="col-xl-5">
        @can('escalate', $incident)
            <div class="card mb-3 border-warning-subtle">
                <div class="card-header"><i class="bi bi-arrow-up-circle" aria-hidden="true"></i>Escalar al DECE</div>
                <div class="card-body">
                    <p class="small text-secondary">Úselo si la situación supera lo que puede atender o requiere una decisión del DECE. Se notifica al equipo y queda en el historial.</p>
                    <form method="POST" action="{{ route('casos.escalate', $incident) }}" novalidate data-confirm="Se notificará al DECE con el motivo indicado." data-confirm-title="Escalar el caso" data-confirm-ok="Escalar">
                        @csrf
                        <x-form.textarea name="reason" label="Motivo" required rows="2" maxlength="400" />
                        <button type="submit" class="btn btn-outline-primary btn-sm">Escalar al DECE</button>
                    </form>
                </div>
            </div>
        @endcan
        @if ($canManage)
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Cambiar estado</div>
                <div class="card-body">
                    @if ($transitions)
                        <form method="POST" action="{{ route('casos.transition', $incident) }}" novalidate>
                            @csrf
                            <x-form.select name="status" label="Nuevo estado" required id="transition-status"
                                           :options="collect($transitions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                            <x-form.textarea name="justification" label="Justificación" rows="2" maxlength="2000"
                                             help="Obligatoria para requerir información, derivar, cerrar o reabrir." />
                            <div id="closure-group" hidden>
                                <x-form.textarea name="closure_summary" label="Resumen de cierre" rows="3" maxlength="5000"
                                                 help="Qué se hizo y por qué se cierra. Cerrar no elimina el historial." />
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit" data-confirm-status>Aplicar cambio</button>
                        </form>
                    @else
                        <p class="small text-secondary mb-0">No hay transiciones disponibles.</p>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-flag me-1" aria-hidden="true"></i>Prioridad</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('casos.priority', $incident) }}" class="row g-2" novalidate>
                        @csrf
                        <div class="col-5">
                            <label for="priority" class="form-label small">Prioridad</label>
                            <select id="priority" name="priority" class="form-select form-select-sm">
                                @foreach ($priorities as $v => $l)<option value="{{ $v }}" @selected($incident->priority->value === $v)>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-7">
                            <label for="priority-reason" class="form-label small">Motivo</label>
                            <input id="priority-reason" name="reason" class="form-control form-control-sm" maxlength="300" required>
                        </div>
                        <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="submit">Actualizar prioridad</button></div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><i class="bi bi-people me-1" aria-hidden="true"></i>Responsables</div>
            @php $active = $incident->assignments->whereNull('unassigned_at'); $past = $incident->assignments->whereNotNull('unassigned_at'); @endphp
            @if ($active->isEmpty())
                <div class="card-body small text-secondary">Sin responsables asignados.</div>
            @else
                <ul class="list-group list-group-flush small">
                    @foreach ($active as $a)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span><span class="fw-semibold">{{ $a->user->name }}</span> <span class="badge text-bg-light border">{{ $a->assignment_role->label() }}</span></span>
                                <span class="text-secondary text-nowrap">{{ $a->assigned_at->format('d/m/Y') }}</span>
                            </div>
                            @if ($a->notes)<div class="text-secondary">{{ $a->notes }}</div>@endif
                            @can('assign', $incident)
                                <details class="mt-1">
                                    <summary class="text-danger">Finalizar asignación</summary>
                                    <form method="POST" action="{{ route('casos.assign.release', [$incident, $a]) }}" class="d-flex gap-2 mt-1" novalidate>
                                        @csrf @method('PATCH')
                                        <label class="visually-hidden" for="rel-{{ $a->id }}">Motivo</label>
                                        <input id="rel-{{ $a->id }}" name="reason" class="form-control form-control-sm" placeholder="Motivo" maxlength="500" required>
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Finalizar</button>
                                    </form>
                                </details>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($past->isNotEmpty())
                <div class="card-body border-top small text-secondary">
                    <div class="fw-semibold mb-1">Asignaciones anteriores</div>
                    @foreach ($past as $a)
                        <div>{{ $a->user->name }} ({{ $a->assigned_at->format('d/m/Y') }} – {{ $a->unassigned_at->format('d/m/Y') }}): {{ $a->unassignment_reason }}</div>
                    @endforeach
                </div>
            @endif
            @can('assign', $incident)
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('casos.assign', $incident) }}" novalidate>
                        @csrf
                        <x-form.select name="user_id" label="Asignar responsable" :options="$assignables ?? []" required />
                        <x-form.select name="assignment_role" label="Tipo" :options="$assignmentRoles" selected="principal" :placeholder="false" />
                        <x-form.input name="notes" label="Indicaciones (opcional)" maxlength="500" />
                        <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Asignar y notificar</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</div>
