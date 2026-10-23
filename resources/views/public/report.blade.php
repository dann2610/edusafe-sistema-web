<x-layouts.public title="Reportar un incidente">
    <div class="container py-4" style="max-width:780px">
        <nav aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('public.home') }}">Inicio</a></li><li class="breadcrumb-item active" aria-current="page">Reportar</li></ol></nav>
        <h1 class="h3 fw-bold mb-2" style="color:var(--tic-navy)">Reportar un incidente</h1>
        <p class="text-secondary">Completa lo que sepas. Los campos con <span class="required-mark">*</span> son obligatorios.</p>

        <div class="alert alert-warning d-flex gap-2" role="note">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <div><strong>Si alguien está en peligro ahora mismo, llama al ECU 911</strong> y avisa a un adulto de la institución. El DECE revisa estos reportes en horario institucional.</div>
        </div>

        <div class="card mb-3"><div class="card-body small">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Qué datos pedimos y para qué</div>
            Te pedimos describir la situación y, si quieres, un dato de contacto. Se usan solo para que el DECE atienda el caso.
            <strong>No escribas cédulas, direcciones ni fotos de menores.</strong> Este canal no es anónimo:
            registra datos técnicos de seguridad, como la dirección IP, para evitar abusos.
            <a href="{{ route('public.privacy') }}" target="_blank" rel="noopener">Ver aviso de privacidad</a>.
        </div></div>

        <x-flash />

        <form method="POST" action="{{ route('public.report.store') }}" class="card" novalidate>
            <div class="card-body p-4">
                @csrf
                <input type="hidden" name="form_token" value="{{ $token }}">
                {{-- Campo trampa contra envíos automatizados: debe quedar vacío --}}
                <div class="hp-field" aria-hidden="true">
                    <label for="sitio_web">No completar este campo</label>
                    <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
                </div>

                <fieldset class="mb-2">
                    <legend class="h6 fw-bold">1. ¿Qué ocurrió?</legend>
                    <x-form.select name="incident_category_id" label="Tipo de situación" required
                                   :options="$categories->pluck('name', 'id')->all()" />
                    <x-form.textarea name="description" label="Describe lo ocurrido" required rows="6" maxlength="5000"
                                     help="Qué pasó, quiénes participaron (puedes usar el curso o una descripción) y si ya ocurrió antes. Mínimo 20 caracteres." />
                    <div class="row">
                        <div class="col-md-6"><x-form.input name="occurred_at" type="datetime-local" label="Fecha y hora aproximadas" help="Si no la recuerdas con exactitud, indica la más cercana." /></div>
                        <div class="col-md-6"><x-form.input name="location" label="Lugar aproximado" maxlength="255" help="Ejemplo: patio, aula de 9.º B, redes sociales." /></div>
                    </div>
                    <x-form.checkbox name="immediate_risk" label="Creo que existe un riesgo inmediato para alguien"
                                     help="Marcarlo da prioridad urgente a tu reporte. Si el peligro es ahora, llama también al ECU 911." />
                </fieldset>

                <fieldset class="mb-2">
                    <legend class="h6 fw-bold">2. Sobre ti</legend>
                    <x-form.select name="reporter_relationship" label="Tu relación con la institución" required :options="$relationships" />
                    <div class="row">
                        <div class="col-md-6"><x-form.input name="reporter_contact_name" label="Tu nombre (opcional)" maxlength="150" autocomplete="name" /></div>
                        <div class="col-md-6"><x-form.input name="reporter_contact" label="Teléfono o correo de contacto (opcional)" maxlength="150"
                                                            help="Solo si deseas que el DECE pueda comunicarse contigo." /></div>
                    </div>
                </fieldset>

                <x-form.checkbox name="privacy" label="Leí el aviso de privacidad y acepto que esta información sea usada por el DECE para atender la situación." />

                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-send me-2" aria-hidden="true"></i>Enviar reporte</button>
            </div>
        </form>
    </div>
</x-layouts.public>
