<x-layouts.app :title="$referral->incident->code" :heading="'Derivación del caso '.$referral->incident->code"
                :lead="$referral->incident->category->name"
                :breadcrumbs="['Casos derivados' => route('entity.referrals.index'), $referral->incident->code => null]">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <dl class="row kv mb-3">
                        <dt class="col-sm-4">Entidad</dt><dd class="col-sm-8">{{ $referral->externalEntity->name }}</dd>
                        <dt class="col-sm-4">Fecha de derivación</dt><dd class="col-sm-8">{{ $referral->referred_on->format('d/m/Y') }}</dd>
                        <dt class="col-sm-4">Medio</dt><dd class="col-sm-8">{{ $referral->channel->label() }}</dd>
                        <dt class="col-sm-4">Oficio / referencia</dt><dd class="col-sm-8">{{ $referral->reference_number ?? '—' }}</dd>
                        <dt class="col-sm-4">Estado de la gestión</dt><dd class="col-sm-8"><span class="badge {{ $referral->management_status->badgeClass() }}">{{ $referral->management_status->label() }}</span></dd>
                    </dl>
                    <div class="fw-semibold small mb-1">Motivo de la derivación</div>
                    <p class="description-text">{{ $referral->reason }}</p>
                    <div class="fw-semibold small mb-1">Información compartida por la institución</div>
                    <p class="description-text mb-0">{{ $referral->shared_summary ?? 'La institución no registró información adicional en el sistema. Consulte el oficio recibido por el canal oficial.' }}</p>
                </div>
            </div>
            <p class="small text-secondary mt-3">Para ampliar información, comuníquese con el DECE de la institución por los canales oficiales indicando el código del caso.</p>
        </div>
    </div>
</x-layouts.app>
