@php
    $user = auth()->user();
    $canManage = $user->can('manage', $incident);
    $canFollow = $user->can('addFollowUp', $incident) && $incident->status->isOpen();
    $canMeasures = $user->can('manageMeasures', $incident);
    $canReferrals = $user->can('manageReferrals', $incident);
    $back = $user->can('casos.bandeja') ? ['Bandeja de casos' => route('casos.inbox')] : ['Casos asignados' => route('casos.assigned')];
@endphp
<x-layouts.app :title="$incident->code" :heading="'Caso '.$incident->code" :lead="$incident->category->name"
                :breadcrumbs="$back + [$incident->code => null]">
    <x-slot:actions>
        <x-status-badge :status="$incident->status" class="fs-6" />
        <x-priority-badge :priority="$incident->priority" class="fs-6" />
    </x-slot:actions>

    <div class="note mb-3" role="note">
        <i class="bi bi-lock-fill" aria-hidden="true"></i>
        <span>Expediente confidencial. Su consulta quedó registrada. El sistema documenta las decisiones institucionales; no determina la veracidad del reporte ni responsabilidades.</span>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        @php
            $modules = app(\App\Services\ModuleService::class);
            $tabs = ['resumen' => 'Resumen', 'seguimientos' => 'Seguimientos ('.$incident->followUps->count().')'];
            if ($modules->enabled('medidas')) { $tabs['medidas'] = 'Medidas ('.$incident->measures->count().')'; }
            if ($modules->enabled('derivaciones')) { $tabs['derivaciones'] = 'Derivaciones ('.$incident->referrals->count().')'; }
            $tabs['historial'] = 'Historial';
        @endphp
        @foreach ($tabs as $tab => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link @if ($loop->first) active @endif" id="tab-{{ $tab }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $tab }}" type="button" role="tab" aria-controls="pane-{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="pane-resumen" role="tabpanel" aria-labelledby="tab-resumen" tabindex="0">
            @include('cases.partials.summary')
        </div>
        <div class="tab-pane fade" id="pane-seguimientos" role="tabpanel" aria-labelledby="tab-seguimientos" tabindex="0">
            @include('cases.partials.followups')
        </div>
        @isset($tabs['medidas'])
            <div class="tab-pane fade" id="pane-medidas" role="tabpanel" aria-labelledby="tab-medidas" tabindex="0">
                @include('cases.partials.measures')
            </div>
        @endisset
        @isset($tabs['derivaciones'])
            <div class="tab-pane fade" id="pane-derivaciones" role="tabpanel" aria-labelledby="tab-derivaciones" tabindex="0">
                @include('cases.partials.referrals')
            </div>
        @endisset
        <div class="tab-pane fade" id="pane-historial" role="tabpanel" aria-labelledby="tab-historial" tabindex="0">
            @include('cases.partials.timeline')
        </div>
    </div>
</x-layouts.app>
