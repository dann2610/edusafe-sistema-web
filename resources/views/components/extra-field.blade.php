@props(['field', 'disabled' => false])
{{--
    Campo adicional de una categoría. El tipo y las opciones son datos de la
    configuración; todo se imprime escapado. La validación real está en el
    servidor (FieldType::rules). "required" lo activa el script solo cuando la
    categoría está seleccionada (data-required), para no bloquear otras.
--}}
@php
    /** @var \App\Models\IncidentCategoryField $field */
    $name = 'extra['.$field->key.']';
    $errKey = 'extra.'.$field->key;
    $id = 'extra-'.$field->incident_category_id.'-'.$field->key;
    $value = old($errKey);
    $hasError = $errors->has($errKey);
    $type = $field->type->value;
@endphp
<div class="mb-3">
    @if ($type === 'casilla')
        <div class="form-check">
            <input type="hidden" name="{{ $name }}" value="0" @disabled($disabled)>
            <input type="checkbox" class="form-check-input @if ($hasError) is-invalid @endif" id="{{ $id }}" name="{{ $name }}" value="1" @checked($value) @disabled($disabled)>
            <label class="form-check-label" for="{{ $id }}">{{ $field->label }}</label>
            @if ($hasError)<div class="invalid-feedback">{{ $errors->first($errKey) }}</div>@endif
        </div>
    @else
        <label for="{{ $id }}" class="form-label">{{ $field->label }}@if ($field->is_required)<span class="required-mark" aria-hidden="true"> *</span>@endif</label>
        @switch($type)
            @case('texto_largo')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="3" maxlength="2000" class="form-control @if ($hasError) is-invalid @endif"
                          @if ($field->is_required) data-required="1" @endif @disabled($disabled)>{{ $value }}</textarea>
                @break
            @case('lista')
                <select id="{{ $id }}" name="{{ $name }}" class="form-select @if ($hasError) is-invalid @endif" @if ($field->is_required) data-required="1" @endif @disabled($disabled)>
                    <option value="">Seleccione…</option>
                    @foreach ($field->options ?? [] as $option)
                        <option value="{{ str_replace(',', '', $option) }}" @selected($value === str_replace(',', '', $option))>{{ $option }}</option>
                    @endforeach
                </select>
                @break
            @default
                <input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                       type="{{ ['fecha' => 'date', 'hora' => 'time'][$type] ?? 'text' }}"
                       @if ($type === 'texto') maxlength="200" @endif
                       @if ($type === 'fecha') max="{{ now()->toDateString() }}" @endif
                       class="form-control @if ($hasError) is-invalid @endif" @if ($field->is_required) data-required="1" @endif @disabled($disabled)>
        @endswitch
        @if ($hasError)<div class="invalid-feedback">{{ $errors->first($errKey) }}</div>@endif
    @endif
    @if ($field->help_text)<div class="form-text">{{ $field->help_text }}</div>@endif
</div>
