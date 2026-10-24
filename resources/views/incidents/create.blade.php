<x-layouts.app title="Registrar incidente" heading="Registrar incidente"
                lead="El DECE recibirá una notificación al guardar. Registre solo los datos necesarios."
                :breadcrumbs="['Registrar incidente' => null]">
    <div class="alert alert-warning d-flex gap-2" role="note">
        <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
        <div>Si hay un <strong>riesgo inmediato</strong>, active primero el protocolo institucional y llame al <strong>ECU 911</strong>. Este registro no reemplaza esa actuación.</div>
    </div>

    <form method="POST" action="{{ route('incidentes.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="card mb-3">
            <div class="card-header">1. Datos del hecho</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><x-form.select name="incident_category_id" label="Categoría" required :options="$categories->pluck('name', 'id')->all()" data-category-select /></div>
                    <div class="col-md-6"><x-form.select name="course_id" label="Curso relacionado" :options="$courses" placeholder="No aplica / no se sabe" /></div>
                    <div class="col-md-6"><x-form.input name="occurred_at" type="datetime-local" label="Fecha y hora aproximadas" /></div>
                    <div class="col-md-6"><x-form.input name="location" label="Lugar aproximado" maxlength="255" help="Ejemplo: patio central, laboratorio, redes sociales." /></div>
                    <div class="col-md-6"><x-form.select name="is_ongoing" label="¿La situación continúa?" :options="\App\Models\Incident::YES_NO_UNKNOWN" placeholder="Sin indicar" /></div>
                </div>
                @php $selectedCategory = (string) old('incident_category_id'); @endphp
                @foreach ($categories as $category)
                    @if ($category->activeFields->isNotEmpty())
                        <div class="note d-block mb-3" data-category-fields="{{ $category->id }}" @if ($selectedCategory !== (string) $category->id) hidden @endif>
                            <p class="section-title mb-2">Información adicional: {{ $category->name }}</p>
                            @foreach ($category->activeFields as $field)
                                <x-extra-field :field="$field" :disabled="$selectedCategory !== (string) $category->id" />
                            @endforeach
                        </div>
                    @endif
                @endforeach
                <x-form.textarea name="description" label="Descripción de lo ocurrido" required rows="6" maxlength="5000"
                                 help="Hechos observados o comunicados, sin juicios ni conclusiones. Mínimo 20 caracteres." />
                <x-form.checkbox name="immediate_risk" label="Se comunicó un posible riesgo inmediato" help="Asigna prioridad urgente y alerta al DECE." />
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>2. Personas involucradas <span class="fw-normal text-secondary small">(solo si es necesario)</span></span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="participants"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Agregar</button>
            </div>
            <div class="card-body">
                <p class="small text-secondary">Seleccione al estudiante o, si no es estudiante, descríbalo de forma general (por ejemplo, «persona adulta externa»). El rol «señalado» no implica responsabilidad.</p>
                <div id="participants" data-next-index="{{ count(old('participants', [])) }}">
                    @foreach (old('participants', []) as $i => $p)
                        @include('incidents._participant-row', ['i' => $i, 'p' => $p])
                    @endforeach
                </div>
                <template id="participants-template">
                    @include('incidents._participant-row', ['i' => '__INDEX__', 'p' => []])
                </template>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">3. Evidencias <span class="fw-normal text-secondary small">(opcional)</span></div>
            <div class="card-body">
                <x-form.input name="attachments[]" id="attachments" type="file" label="Archivos" multiple accept=".pdf,.jpg,.jpeg,.png,.docx"
                              help="Hasta {{ $maxFiles }} archivos PDF, JPG, PNG o DOCX de máximo {{ $maxMb }} MB cada uno. Se guardan de forma privada. No adjunte imágenes íntimas de menores." />
                @foreach ($errors->get('attachments.*') as $messages)
                    @foreach ($messages as $m)<div class="text-danger small">{{ $m }}</div>@endforeach
                @endforeach
                @error('attachments')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1" aria-hidden="true"></i>Registrar incidente</button>
            <a href="{{ route('panel') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
