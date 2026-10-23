<div class="row g-3">
    <div class="{{ $canMeasures && $incident->status->isOpen() ? 'col-xl-7' : 'col-12' }}">
        @forelse ($incident->measures as $m)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <span class="fw-semibold">{{ $m->type->name }}</span>
                            <span class="badge text-bg-light border ms-1">{{ $m->type->kind->label() }}</span>
                        </div>
                        <span class="badge {{ $m->compliance_status->badgeClass() }}">{{ $m->compliance_status->label() }}</span>
                    </div>
                    <p class="description-text small mb-2">{{ $m->description }}</p>
                    <dl class="row kv small mb-0">
                        <dt class="col-sm-4">Responsable</dt><dd class="col-sm-8">{{ $m->responsible->name }}</dd>
                        <dt class="col-sm-4">Inicio</dt><dd class="col-sm-8">{{ $m->starts_on->format('d/m/Y') }}</dd>
                        @if ($m->review_on)<dt class="col-sm-4">Revisión</dt><dd class="col-sm-8">{{ $m->review_on->format('d/m/Y') }}</dd>@endif
                        @if ($m->ends_on)<dt class="col-sm-4">Finalización</dt><dd class="col-sm-8">{{ $m->ends_on->format('d/m/Y') }}</dd>@endif
                        @if ($m->observations)<dt class="col-sm-4">Observaciones</dt><dd class="col-sm-8">{{ $m->observations }}</dd>@endif
                    </dl>
                    @if ($canMeasures)
                        <details class="mt-2">
                            <summary class="small">Actualizar cumplimiento</summary>
                            <form method="POST" action="{{ route('casos.measures.update', [$incident, $m]) }}" class="row g-2 mt-1" novalidate>
                                @csrf @method('PATCH')
                                <div class="col-md-4"><label class="form-label small" for="m{{ $m->id }}-st">Estado</label>
                                    <select id="m{{ $m->id }}-st" name="compliance_status" class="form-select form-select-sm">@foreach ($compliance as $v => $l)<option value="{{ $v }}" @selected($m->compliance_status->value === $v)>{{ $l }}</option>@endforeach</select></div>
                                <div class="col-md-4"><label class="form-label small" for="m{{ $m->id }}-rev">Revisión</label><input type="date" id="m{{ $m->id }}-rev" name="review_on" value="{{ $m->review_on?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                <div class="col-md-4"><label class="form-label small" for="m{{ $m->id }}-end">Finalización</label><input type="date" id="m{{ $m->id }}-end" name="ends_on" value="{{ $m->ends_on?->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                                <div class="col-12"><label class="form-label small" for="m{{ $m->id }}-obs">Observaciones</label><textarea id="m{{ $m->id }}-obs" name="observations" rows="2" maxlength="2000" class="form-control form-control-sm">{{ $m->observations }}</textarea></div>
                                <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="submit">Guardar</button></div>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        @empty
            <div class="card"><x-empty icon="bi-shield" text="No se han registrado medidas." /></div>
        @endforelse
    </div>

    @if ($canMeasures && $incident->status->isOpen())
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-shield-plus me-1" aria-hidden="true"></i>Registrar medida</div>
                <div class="card-body">
                    <p class="small text-secondary">Documente la decisión institucional adoptada. El sistema no decide sanciones.</p>
                    <form method="POST" action="{{ route('casos.measures.store', $incident) }}" novalidate>
                        @csrf
                        <x-form.select name="measure_type_id" label="Tipo de medida" :options="$measureTypes" required />
                        <x-form.textarea name="description" id="measure-description" label="Descripción" rows="3" maxlength="3000" required />
                        <x-form.select name="responsible_user_id" label="Responsable" :options="$responsibles" required />
                        <div class="row">
                            <div class="col-md-4"><x-form.input name="starts_on" type="date" label="Inicio" :value="now()->format('Y-m-d')" required /></div>
                            <div class="col-md-4"><x-form.input name="review_on" type="date" label="Revisión" /></div>
                            <div class="col-md-4"><x-form.input name="ends_on" type="date" label="Fin" /></div>
                        </div>
                        <x-form.textarea name="observations" id="measure-observations" label="Observaciones" rows="2" maxlength="2000" />
                        <button class="btn btn-primary btn-sm" type="submit">Registrar medida</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
