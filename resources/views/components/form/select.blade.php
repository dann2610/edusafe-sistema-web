@props([
    'name',
    'label',
    'options' => [],        // [valor => etiqueta]
    'selected' => null,
    'required' => false,
    'placeholder' => 'Seleccione…',
    'help' => null,
    'id' => null,
])
@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
    $current = (string) old($name, $selected);
    $describedBy = trim(($help ? $id.'-help ' : '').($hasError ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span>@endif
    </label>
    <select id="{{ $id }}" name="{{ $name }}"
            class="form-select @if ($hasError) is-invalid @endif"
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class') }}>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected($current === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hasError)
        <div id="{{ $id }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
    @if ($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
</div>
