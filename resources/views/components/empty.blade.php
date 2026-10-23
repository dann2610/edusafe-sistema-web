@props(['icon' => 'bi-inbox', 'text'])
<div class="text-center text-secondary py-5 px-3">
    <i class="bi {{ $icon }} d-block mb-2" style="font-size:2rem" aria-hidden="true"></i>
    {{ $text }}
</div>
