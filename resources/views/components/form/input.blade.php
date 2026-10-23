@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'autocomplete' => null,
    'id' => null,
])
@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
    $describedBy = trim(($help ? $id.'-help ' : '').($hasError ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span>@endif
    </label>
    @if ($type === 'password')
        <div class="input-group has-validation">
            <input type="password" id="{{ $id }}" name="{{ $name }}"
                   class="form-control @if ($hasError) is-invalid @endif"
                   @if ($required) required aria-required="true" @endif
                   @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                   @if ($hasError) aria-invalid="true" @endif
                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                   {{ $attributes->except('class') }}>
            <button class="btn btn-outline-secondary" type="button" data-toggle-password="{{ $id }}"
                    aria-label="Mostrar contraseña" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
            @if ($hasError)
                <div id="{{ $id }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
            @endif
        </div>
    @else
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}"
               value="{{ old($name, $value) }}"
               class="form-control @if ($hasError) is-invalid @endif"
               @if ($required) required aria-required="true" @endif
               @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
               @if ($hasError) aria-invalid="true" @endif
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               {{ $attributes->except('class') }}>
        @if ($hasError)
            <div id="{{ $id }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
        @endif
    @endif
    @if ($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
</div>
