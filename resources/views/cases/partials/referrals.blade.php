<div class="row g-3">
    <div class="{{ $canReferrals && $incident->status->isOpen() ? 'col-xl-7' : 'col-12' }}">
        <p class="small text-secondary">Las derivaciones se realizan por los canales oficiales de cada entidad. Aquí solo se documenta la actuación; no existe integración automática con sistemas externos.</p>
        @forelse ($incident->referrals as $r)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <span class="fw-semibold">{{ $r->externalEntity->name }}</span>
                        <span class="badge {{ $r->management_status->badgeClass() }}">{{ $r->management_status->label() }}</span>
                    </div>
                    <dl class="row kv small mb-2">
                        <dt class="col-sm-4">Fecha</dt><dd class="col-sm-8">{{ $r->referred_on->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Medio</dt><dd class="col-sm-8">{{ $r->channel->label() }}</dd>
                        <dt class="col-sm-4">Oficio / referencia</dt><dd class="col-sm-8">{{ $r->reference_number ?? '—' }}</dd>
                        <dt class="col-sm-4">Registrada por</dt><dd class="col-sm-8">{{ $r->registeredBy->name }}</dd>
                    </dl>
                    <div class="small fw-semibold">Motivo</div>
                    <p class="description-text small">{{ $r->reason }}</p>
                    <div class="small fw-semibold">Información compartida con la entidad</div>
                    <p class="description-text small mb-2">{{ $r->shared_summary ?? 'No se registró información compartida en el sistema.' }}</p>
                    @if ($r->observations)<p class="small mb-0"><span class="text-secondary">Observaciones:</span> {{ $r->observations }}</p>@endif
                    @if ($canReferrals)
                        <details class="mt-2">
                            <summary class="small">Actualizar gestión</summary>
                            <form method="POST" action="{{ route('casos.referrals.update', [$incident, $r]) }}" class="row g-2 mt-1" novalidate>
                                @csrf @method('PATCH')
                                <div class="col-md-6"><label class="form-label small" for="r{{ $r->id }}-st">Estado</label>
                                    <select id="r{{ $r->id }}-st" name="management_status" class="form-select form-select-sm">@foreach ($referralStatuses as $v => $l)<option value="{{ $v }}" @selected($r->management_status->value === $v)>{{ $l }}</option>@endforeach</select></div>
                                <div class="col-md-6"><label class="form-label small" for="r{{ $r->id }}-ref">Oficio / referencia</label><input id="r{{ $r->id }}-ref" name="reference_number" value="{{ $r->reference_number }}" maxlength="80" class="form-control form-control-sm"></div>
                                <div class="col-12"><label class="form-label small" for="r{{ $r->id }}-obs">Observaciones</label><textarea id="r{{ $r->id }}-obs" name="observations" rows="2" maxlength="2000" class="form-control form-control-sm">{{ $r->observations }}</textarea></div>
                                <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="submit">Guardar</button></div>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        @empty
            <div class="card"><x-empty icon="bi-send" text="El caso no ha sido derivado." /></div>
        @endforelse
    </div>

    @if ($canReferrals && $incident->status->isOpen())
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-send-plus me-1" aria-hidden="true"></i>Registrar derivación</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('casos.referrals.store', $incident) }}" novalidate>
                        @csrf
                        <x-form.select name="external_entity_id" label="Entidad destinataria" :options="$entities" required />
                        <div class="row">
                            <div class="col-md-6"><x-form.input name="referred_on" type="date" label="Fecha" :value="now()->format('Y-m-d')" required /></div>
                            <div class="col-md-6"><x-form.select name="channel" label="Medio" :options="$channels" required /></div>
                        </div>
                        <x-form.input name="reference_number" id="referral-reference" label="N.º de oficio o referencia" maxlength="80" />
                        <x-form.textarea name="reason" label="Motivo" rows="3" maxlength="3000" required />
                        <x-form.textarea name="shared_summary" label="Información compartida con la entidad" rows="3" maxlength="3000"
                                         help="Es lo único que verá el personal de la entidad en este sistema. Incluya solo lo necesario." />
                        <x-form.textarea name="observations" id="referral-observations" label="Observaciones internas" rows="2" maxlength="2000" />
                        <button class="btn btn-primary btn-sm" type="submit" data-confirm="¿Registrar la derivación? El caso pasará a estado «Derivado».">Registrar derivación</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
