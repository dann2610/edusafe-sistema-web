@props(['priority'])
@if ($priority->value !== 'normal')
    <span {{ $attributes->merge(['class' => 'badge '.$priority->badgeClass()]) }}>
        @if ($priority->value === 'urgente')<i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>@endif{{ $priority->label() }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'badge text-bg-light border']) }}>Normal</span>
@endif
