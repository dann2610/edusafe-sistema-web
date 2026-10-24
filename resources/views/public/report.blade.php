<x-layouts.public title="Reportar un incidente">
@php
    $steps = ['incidente' => 'Qué pasó', 'personas' => 'Personas', 'reporta' => 'Sobre ti'];
    if ($allowEvidence) { $steps['evidencias'] = 'Evidencias'; }
    $steps['revision'] = 'Revisar y enviar';
    $old = fn ($k, $d = null) => old($k, $d);
    $personRoles = ['afectado' => 'Persona afectada', 'senalado' => 'Señalada como posible responsable', 'testigo' => 'Testigo'];
    $persons = old('persons', []);
    $selectedCategory = (string) old('incident_category_id');
    $mode = old('reporter_mode', $allowAnonymous ? null : 'con_contacto');
@endphp
<div class="container py-4 py-md-5" style="max-width:820px">
    <nav aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ $institution->publicRoute('home') }}">Inicio</a></li><li class="breadcrumb-item active" aria-current="page">Reportar un incidente</li></ol></nav>
    <h1 class="mb-2">Reportar un incidente</h1>
    <p class="text-secondary mb-4">Completa lo que sepas; puedes dejar en blanco lo que no conozcas. Los campos con <span class="required-mark">*</span> son obligatorios. Al final verás un resumen antes de enviar.</p>

    <div class="alert alert-danger d-flex gap-2 mb-4" role="note">
        <i class="bi bi-exclamation-octagon-fill mt-1" aria-hidden="true"></i>
        <div><strong>Si alguien está en peligro ahora mismo, llama al <a href="tel:911" class="alert-link">ECU 911</a></strong> y avisa a un adulto de la institución. El DECE revisa estos reportes en horario institucional.</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert" id="form-errors" tabindex="-1">
            <strong>Revisa la información marcada.</strong>
            <ul class="mb-0 mt-1">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif
    <x-flash />

    <form method="POST" action="{{ $institution->publicRoute('report.store') }}" class="form-panel" data-wizard novalidate
          @if ($allowEvidence) enctype="multipart/form-data" @endif aria-describedby="wizard-progress">
        @csrf
        <input type="hidden" name="form_token" value="{{ $token }}">
        <div class="hp-field" aria-hidden="true">
            <label for="sitio_web">No completar este campo</label>
            <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
        </div>

        {{-- Indicador de progreso (visible con JavaScript) --}}
        <div class="js-only flex-column mb-2">
            <p id="wizard-progress" class="wizard-progress-text mb-2" aria-live="polite"></p>
            <ol class="wizard-steps" aria-hidden="true">
                @foreach ($steps as $key => $label)
                    <li data-step-indicator="{{ $key }}"><span class="ws"><span class="ws-label">{{ $label }}</span></span></li>
                @endforeach
            </ol>
        </div>

        {{-- A. Incidente --}}
        <section class="wizard-panel" data-step="incidente" data-title="Qué pasó" aria-labelledby="t-incidente">
            <h2 id="t-incidente" class="mb-3">¿Qué pasó?</h2>

            <fieldset class="mb-4">
                <legend class="form-label mb-2">Tipo de situación <span class="required-mark" aria-hidden="true">*</span></legend>
                @if ($categories->isEmpty())
                    <div class="note"><i class="bi bi-info-circle" aria-hidden="true"></i><div>No hay tipos de situación configurados. Acércate al DECE.</div></div>
                @endif
                <div class="d-grid gap-2" data-review-radio="Tipo de situación">
                    @foreach ($categories as $category)
                        <label class="choice">
                            <input type="radio" name="incident_category_id" value="{{ $category->id }}" required data-category-choice
                                   @checked($selectedCategory === (string) $category->id) @if ($errors->has('incident_category_id')) aria-invalid="true" @endif>
                            <span class="flex-column align-items-start gap-0">
                                <span class="choice-text">{{ $category->name }}</span>
                                @if ($category->description)<small class="text-secondary fw-normal">{{ $category->description }}</small>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('incident_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </fieldset>

            @foreach ($categories as $category)
                @if ($category->activeFields->isNotEmpty())
                    <div class="mb-2" data-category-fields="{{ $category->id }}" @if ($selectedCategory !== (string) $category->id) hidden @endif>
                        <p class="section-title mb-2">Información adicional sobre «{{ $category->name }}»</p>
                        @foreach ($category->activeFields as $field)
                            <x-extra-field :field="$field" :disabled="$selectedCategory !== (string) $category->id" />
                        @endforeach
                    </div>
                @endif
            @endforeach

            <x-form.textarea name="description" label="Describe lo ocurrido" required rows="6" maxlength="5000" minlength="20" data-review="Descripción"
                             help="Qué pasó y si ya ocurrió antes. Puedes referirte a las personas por su curso o con una descripción. No escribas cédulas ni direcciones. Mínimo 20 caracteres." />

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="occurred_date" class="form-label">Fecha aproximada</label>
                    <input type="date" id="occurred_date" name="occurred_date" value="{{ old('occurred_date') }}" max="{{ now()->toDateString() }}" data-review="Fecha aproximada"
                           class="form-control @error('occurred_date') is-invalid @enderror" @error('occurred_date') aria-invalid="true" @enderror>
                    @error('occurred_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="date_unknown" name="date_unknown" value="1" @checked(old('date_unknown')) data-disables="occurred_date,occurred_time,time_unknown">
                        <label class="form-check-label" for="date_unknown">No recuerdo la fecha</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="occurred_time" class="form-label">Hora aproximada</label>
                    <input type="time" id="occurred_time" name="occurred_time" value="{{ old('occurred_time') }}" data-review="Hora aproximada"
                           class="form-control @error('occurred_time') is-invalid @enderror">
                    @error('occurred_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="time_unknown" name="time_unknown" value="1" @checked(old('time_unknown')) data-disables="occurred_time">
                        <label class="form-check-label" for="time_unknown">No recuerdo la hora</label>
                    </div>
                </div>
            </div>

            <x-form.input name="location" label="Lugar aproximado" maxlength="255" data-review="Lugar" help="Por ejemplo: patio, aula de 9.º B, baños del segundo piso, redes sociales." />

            <fieldset class="mb-3">
                <legend class="form-label mb-2">¿La situación continúa ocurriendo? <span class="required-mark" aria-hidden="true">*</span></legend>
                <div class="choice-group" data-review-radio="¿Continúa ocurriendo?">
                    @foreach (['si' => 'Sí', 'no' => 'No', 'no_sabe' => 'No lo sé'] as $v => $l)
                        <label class="choice"><input type="radio" name="is_ongoing" value="{{ $v }}" required @checked(old('is_ongoing') === $v)><span class="choice-text">{{ $l }}</span></label>
                    @endforeach
                </div>
                @error('is_ongoing')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </fieldset>

            <div class="note mb-3" style="background:var(--c-danger-soft);border-color:#f6d0cb">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="immediate_risk" name="immediate_risk" value="1" @checked(old('immediate_risk')) data-review-check="Riesgo inmediato">
                    <label class="form-check-label text-body fw-semibold" for="immediate_risk">Creo que alguien está en riesgo inmediato</label>
                    <div class="form-text">Tu reporte tendrá prioridad urgente. Si el peligro es ahora, llama también al ECU 911.</div>
                </div>
            </div>

            <fieldset class="mb-1">
                <legend class="form-label mb-2">¿Ya informaste a una autoridad o a alguien de la institución? <span class="required-mark" aria-hidden="true">*</span></legend>
                <div class="choice-group" data-review-radio="¿Ya se informó a una autoridad?">
                    @foreach (['si' => 'Sí', 'no' => 'No', 'no_sabe' => 'No lo sé'] as $v => $l)
                        <label class="choice"><input type="radio" name="authority_informed" value="{{ $v }}" required @checked(old('authority_informed') === $v) data-reveals="authority-detail" data-reveal-value="si"><span class="choice-text">{{ $l }}</span></label>
                    @endforeach
                </div>
                @error('authority_informed')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </fieldset>
            <div id="authority-detail" class="mt-3" @if (old('authority_informed') !== 'si') hidden @endif>
                <x-form.input name="authority_informed_detail" label="¿A quién?" maxlength="150" data-review="Se informó a" help="Por ejemplo: tutor del curso, inspección, Policía." />
            </div>
        </section>

        {{-- B. Personas --}}
        <section class="wizard-panel" data-step="personas" data-title="Personas" aria-labelledby="t-personas">
            <h2 id="t-personas" class="mb-1">Personas involucradas</h2>
            <p class="text-secondary mb-3">Opcional. No es necesario escribir nombres: puedes usar una descripción como «estudiante de 10.º A» o «persona adulta externa».</p>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="reporter_is_affected" name="reporter_is_affected" value="1" @checked(old('reporter_is_affected')) data-review-check="Quien reporta es la persona afectada">
                <label class="form-check-label" for="reporter_is_affected">Yo soy la persona afectada</label>
            </div>

            <div id="persons" class="d-grid gap-2 mb-3" data-next-index="{{ count($persons) }}" data-max-rows="5" data-review-rows="Personas mencionadas">
                @foreach ($persons as $i => $p)
                    @include('public._person-row', ['i' => $i, 'p' => $p, 'roles' => $personRoles])
                @endforeach
            </div>
            <template id="persons-template">@include('public._person-row', ['i' => '__INDEX__', 'p' => [], 'roles' => $personRoles])</template>
            <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="persons"><i class="bi bi-plus-lg" aria-hidden="true"></i>Agregar persona</button>
            <p class="form-text mb-0 mt-2">Hasta 5 personas. Sin JavaScript, escribe esta información en la descripción.</p>
        </section>

        {{-- C. Quien reporta --}}
        <section class="wizard-panel" data-step="reporta" data-title="Sobre ti" aria-labelledby="t-reporta">
            <h2 id="t-reporta" class="mb-3">Sobre ti</h2>
            <x-form.select name="reporter_relationship" label="Tu relación con la institución" required :options="$relationships" data-review="Relación con la institución" />

            @if ($allowAnonymous)
                <fieldset class="mb-3">
                    <legend class="form-label mb-2">¿Quieres que el DECE pueda contactarte? <span class="required-mark" aria-hidden="true">*</span></legend>
                    <div class="d-grid gap-2" data-review-radio="Forma de reporte">
                        <label class="choice"><input type="radio" name="reporter_mode" value="con_contacto" required @checked($mode === 'con_contacto') data-reveals="contact-fields" data-reveal-value="con_contacto"><span class="choice-text">Sí, dejaré un dato de contacto</span></label>
                        <label class="choice"><input type="radio" name="reporter_mode" value="sin_contacto" required @checked($mode === 'sin_contacto') data-reveals="contact-fields" data-reveal-value="con_contacto"><span class="choice-text">No, reportaré sin datos de contacto</span></label>
                    </div>
                    @error('reporter_mode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">Sin contacto, el DECE no podrá pedirte más información ni contarte cómo avanza el caso. Aun así, el canal registra datos técnicos de seguridad (como la dirección IP), por lo que no es anónimo por completo.</div>
                </fieldset>
            @else
                <input type="hidden" name="reporter_mode" value="con_contacto">
                <div class="note mb-3"><i class="bi bi-info-circle" aria-hidden="true"></i><div>Esta institución pide un dato de contacto para poder dar seguimiento a cada reporte.</div></div>
            @endif

            <div id="contact-fields" @if ($allowAnonymous && $mode !== 'con_contacto') hidden @endif>
                <div class="row g-3">
                    <div class="col-md-6"><x-form.input name="reporter_contact_name" label="Tu nombre" required maxlength="150" autocomplete="name" data-review="Nombre" /></div>
                    <div class="col-md-6"><x-form.input name="reporter_contact" label="Teléfono o correo" required maxlength="150" data-review="Contacto" help="Solo lo verá el personal autorizado." /></div>
                </div>
            </div>
        </section>

        {{-- D. Evidencias (solo si el módulo está activo) --}}
        @if ($allowEvidence)
            <section class="wizard-panel" data-step="evidencias" data-title="Evidencias" aria-labelledby="t-evidencias">
                <h2 id="t-evidencias" class="mb-1">Evidencias</h2>
                <p class="text-secondary mb-3">Opcional. Puedes adjuntar hasta 2 archivos PDF, JPG o PNG de hasta {{ $maxMb }} MB cada uno, por ejemplo una captura de pantalla.</p>
                <div class="mb-3">
                    <label for="attachments" class="form-label">Archivos</label>
                    <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-review-files="Evidencias" data-max-files="2"
                           class="form-control @if ($errors->has('attachments') || $errors->has('attachments.*')) is-invalid @endif">
                    @if ($errors->has('attachments') || $errors->has('attachments.*'))<div class="invalid-feedback">{{ $errors->first('attachments') ?: $errors->first('attachments.*') }}</div>@endif
                </div>
                <div class="note"><i class="bi bi-shield-exclamation" aria-hidden="true"></i><div><strong>No subas fotos ni videos íntimos, ni imágenes en las que se pueda identificar a menores de edad.</strong> Si tienes ese tipo de material, no lo reenvíes: entrégalo en persona al DECE.</div></div>
                @if ($errors->any())<p class="form-text mt-2 mb-0">Por seguridad, si el formulario tuvo errores debes volver a seleccionar los archivos.</p>@endif
            </section>
        @endif

        {{-- E. Revisión --}}
        <section class="wizard-panel" data-step="revision" data-title="Revisar y enviar" aria-labelledby="t-revision">
            <h2 id="t-revision" class="mb-1">Revisa y envía</h2>
            <p class="text-secondary mb-3">Comprueba que la información sea correcta. Puedes volver a cualquier paso para corregirla.</p>
            <div class="card mb-3 js-only flex-column"><div class="card-body"><dl class="review-list mb-0" id="review-summary"></dl></div></div>

            <x-form.checkbox name="privacy" required>
                <x-slot:label>Leí el <a href="{{ $institution->publicRoute('privacy') }}" target="_blank" rel="noopener">aviso de privacidad</a> y acepto que el personal autorizado use esta información para atender la situación.</x-slot:label>
            </x-form.checkbox>
            <x-form.checkbox name="confirm" required label="Confirmo que revisé la información y que es verdadera según lo que sé." />

            <div class="note mb-1"><i class="bi bi-info-circle" aria-hidden="true"></i><div>Al enviar, recibirás un código. Tu reporte no se considera verificado hasta que el DECE lo revise.</div></div>
            <div class="no-js-only mt-3">
                <button type="submit" class="btn btn-accent btn-lg w-100"><i class="bi bi-send" aria-hidden="true"></i>Enviar reporte</button>
            </div>
        </section>

        <div class="wizard-nav js-only">
            <button type="button" class="btn btn-outline-secondary" data-wizard-prev><i class="bi bi-arrow-left" aria-hidden="true"></i>Anterior</button>
            <button type="button" class="btn btn-primary ms-auto" data-wizard-next>Siguiente<i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            <button type="submit" class="btn btn-accent ms-auto" data-wizard-submit hidden><i class="bi bi-send" aria-hidden="true"></i>Enviar reporte</button>
        </div>
    </form>
</div>
</x-layouts.public>
