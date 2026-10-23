<div class="row g-3">
    <div class="{{ $canFollow ? 'col-xl-7' : 'col-12' }}">
        @php $corrected = $incident->followUps->pluck('corrects_follow_up_id')->filter()->all(); @endphp
        @forelse ($incident->followUps->sortByDesc('performed_at') as $f)
            <div @class(['card followup-card mb-3', 'is-correction' => $f->corrects_follow_up_id, 'is-superseded' => in_array($f->id, $corrected)])>
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <span class="fw-semibold">{{ $f->type->name }}</span>
                            @if ($f->corrects_follow_up_id)<span class="badge text-bg-warning ms-1">Corrección</span>@endif
                            @if (in_array($f->id, $corrected))<span class="badge text-bg-secondary ms-1">Corregido</span>@endif
                        </div>
                        <span class="small text-secondary">{{ $f->performed_at->format('d/m/Y H:i') }} · {{ $f->user->name }}</span>
                    </div>
                    @if ($f->corrects_follow_up_id)
                        <p class="small text-secondary mb-2">Corrige el registro del {{ $f->corrects?->performed_at?->format('d/m/Y H:i') }}. Motivo: {{ $f->correction_reason }}</p>
                    @endif
                    <p class="description-text small mb-2">{{ $f->description }}</p>
                    @if ($f->observations)<p class="small mb-2"><span class="text-secondary">Observaciones:</span> {{ $f->observations }}</p>@endif
                    @if ($f->next_action || $f->next_follow_up_on)
                        <p class="small mb-2"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>
                            {{ $f->next_action ?? 'Próximo seguimiento' }}@if ($f->next_follow_up_on) — {{ $f->next_follow_up_on->format('d/m/Y') }}@endif</p>
                    @endif
                    @foreach ($f->attachments as $a)
                        <a href="{{ route('attachments.download', $a) }}" class="small d-inline-block me-3"><i class="bi bi-paperclip" aria-hidden="true"></i> {{ $a->original_name }}</a>
                    @endforeach
                    @if ($canFollow && ($canManage || $f->user_id === auth()->id()) && ! in_array($f->id, $corrected))
                        <details class="mt-2">
                            <summary class="small">Registrar una corrección</summary>
                            <form method="POST" action="{{ route('casos.followups.correct', [$incident, $f]) }}" class="mt-2" novalidate>
                                @csrf
                                @include('cases.partials._followup-fields', ['prefix' => 'c'.$f->id, 'f' => $f])
                                <div class="mb-2">
                                    <label class="form-label small" for="c{{ $f->id }}-reason">Motivo de la corrección <span class="required-mark">*</span></label>
                                    <input id="c{{ $f->id }}-reason" name="correction_reason" class="form-control form-control-sm" maxlength="500" required>
                                </div>
                                <button class="btn btn-sm btn-outline-warning" type="submit">Guardar corrección</button>
                                <div class="form-text">El registro original no se modifica: queda en el historial junto a la corrección.</div>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        @empty
            <div class="card"><x-empty icon="bi-journal" text="Aún no hay seguimientos registrados." /></div>
        @endforelse
    </div>

    @if ($canFollow)
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-journal-plus me-1" aria-hidden="true"></i>Registrar seguimiento</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('casos.followups.store', $incident) }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        @include('cases.partials._followup-fields', ['prefix' => 'new', 'f' => null])
                        <div class="mb-3">
                            <label class="form-label small" for="new-files">Adjuntos (opcional)</label>
                            <input type="file" id="new-files" name="attachments[]" multiple class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.docx">
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">Guardar seguimiento</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
