@props(['name', 'label', 'checked' => false, 'help' => null, 'id' => null])
@php
    $id = $id ?? $name;
    $isChecked = (bool) old($name, $checked);
    if ($errors->any() && old($name) === null && session()->hasOldInput()) {
        $isChecked = false;
    }
    $hasError = $errors->has($name);
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'form-check mb-3']) }}>
    <input type="checkbox" class="form-check-input @if ($hasError) is-invalid @endif" id="{{ $id }}" name="{{ $name }}" value="1"
           @checked($isChecked) @if ($help) aria-describedby="{{ $id }}-help" @endif {{ $attributes->except('class') }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($hasError)
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
    @if ($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
</div>
