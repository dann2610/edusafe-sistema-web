@props(['icon' => 'bi-inbox', 'title' => null, 'text' => null])
<div {{ $attributes->merge(['class' => 'empty-state']) }} role="status">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @if ($title)<strong>{{ $title }}</strong>@endif
    @if ($text)<span>{{ $text }}</span>@endif
    @if (trim($slot) !== '')<div class="mt-3">{{ $slot }}</div>@endif
</div>
