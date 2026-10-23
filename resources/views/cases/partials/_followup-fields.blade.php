{{-- Campos de seguimiento. $prefix distingue ids cuando hay varios formularios en la página. --}}
@php $useOld = $prefix === 'new'; @endphp
<div class="row g-2">
    <div class="col-md-6 mb-2">
        <label class="form-label small" for="{{ $prefix }}-type">Tipo de actuación <span class="required-mark">*</span></label>
        <select id="{{ $prefix }}-type" name="follow_up_type_id" class="form-select form-select-sm @if ($useOld && $errors->has('follow_up_type_id')) is-invalid @endif" required>
            <option value="">Seleccione…</option>
            @foreach ($followUpTypes ?? [] as $id => $n)
                <option value="{{ $id }}" @selected((string) ($useOld ? old('follow_up_type_id') : $f?->follow_up_type_id) === (string) $id)>{{ $n }}</option>
            @endforeach
        </select>
        @if ($useOld)@error('follow_up_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
    <div class="col-md-6 mb-2">
        <label class="form-label small" for="{{ $prefix }}-at">Fecha y hora <span class="required-mark">*</span></label>
        <input type="datetime-local" id="{{ $prefix }}-at" name="performed_at" required
               value="{{ $useOld ? old('performed_at', now()->format('Y-m-d\TH:i')) : $f?->performed_at?->format('Y-m-d\TH:i') }}"
               class="form-control form-control-sm @if ($useOld && $errors->has('performed_at')) is-invalid @endif">
        @if ($useOld)@error('performed_at')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
</div>
<div class="mb-2">
    <label class="form-label small" for="{{ $prefix }}-desc">Descripción de la actuación <span class="required-mark">*</span></label>
    <textarea id="{{ $prefix }}-desc" name="description" rows="3" maxlength="5000" required
              class="form-control form-control-sm @if ($useOld && $errors->has('description')) is-invalid @endif">{{ $useOld ? old('description') : $f?->description }}</textarea>
    @if ($useOld)@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
</div>
<div class="mb-2">
    <label class="form-label small" for="{{ $prefix }}-obs">Observaciones</label>
    <textarea id="{{ $prefix }}-obs" name="observations" rows="2" maxlength="2000" class="form-control form-control-sm">{{ $useOld ? old('observations') : $f?->observations }}</textarea>
</div>
<div class="row g-2">
    <div class="col-md-7 mb-2">
        <label class="form-label small" for="{{ $prefix }}-next">Próxima acción</label>
        <input id="{{ $prefix }}-next" name="next_action" maxlength="500" class="form-control form-control-sm" value="{{ $useOld ? old('next_action') : $f?->next_action }}">
    </div>
    <div class="col-md-5 mb-2">
        <label class="form-label small" for="{{ $prefix }}-nextdate">Fecha prevista</label>
        <input type="date" id="{{ $prefix }}-nextdate" name="next_follow_up_on" class="form-control form-control-sm @if ($useOld && $errors->has('next_follow_up_on')) is-invalid @endif"
               value="{{ $useOld ? old('next_follow_up_on') : '' }}" min="{{ now()->format('Y-m-d') }}">
        @if ($useOld)@error('next_follow_up_on')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
    </div>
</div>
