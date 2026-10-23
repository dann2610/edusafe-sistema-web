@props(['name', 'label', 'value' => null, 'required' => false, 'help' => null, 'rows' => 4, 'id' => null])
@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
    $describedBy = trim(($help ? $id.'-help ' : '').($hasError ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($required)<span class="required-mark" aria-hidden="true"> *</span>@endif
    </label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
              class="form-control @if ($hasError) is-invalid @endif"
              @if ($required) required aria-required="true" @endif
              @if ($hasError) aria-invalid="true" @endif
              @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
              {{ $attributes->except('class') }}>{{ old($name, $value) }}</textarea>
    @if ($hasError)
        <div id="{{ $id }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
    @if ($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
</div>
