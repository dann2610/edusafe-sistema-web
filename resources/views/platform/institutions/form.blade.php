@php $editing = (bool) $institution; @endphp
<x-layouts.app :title="$editing ? 'Editar institución' : 'Registrar institución'" :heading="$editing ? 'Editar '.$institution->name : 'Registrar institución'"
                :lead="$editing ? null : 'Al guardar se crea el administrador inicial con una contraseña temporal y se habilitan los módulos por defecto.'"
                :breadcrumbs="['Plataforma' => route('platform.dashboard'), 'Instituciones' => route('platform.institutions.index'), ($editing ? 'Editar' : 'Registrar') => null]">
    <form method="POST" action="{{ $editing ? route('platform.institutions.update', $institution) : route('platform.institutions.store') }}" novalidate class="d-grid gap-4" style="max-width:920px">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card"><div class="card-header"><i class="bi bi-building" aria-hidden="true"></i>Datos de la institución</div><div class="card-body">
            <div class="row">
                <div class="col-md-8"><x-form.input name="name" label="Nombre oficial" required maxlength="200" :value="$institution?->name" /></div>
                <div class="col-md-4">
                    @if ($editing)
                        <div class="mb-3"><span class="form-label d-block">Siglas</span><span class="code-display fs-6">{{ $institution->code_prefix }}</span><div class="form-text">No se modifican: forman parte de los códigos de caso.</div></div>
                    @else
                        <x-form.input name="code_prefix" label="Siglas" required maxlength="12" help="De 2 a 12 letras o números. Ej.: UECRC. Se usan en los códigos de caso." />
                    @endif
                </div>
                <div class="col-md-4"><x-form.input name="amie_code" label="Código AMIE" maxlength="20" :value="$institution?->amie_code" help="Código del Ministerio de Educación, si se conoce." /></div>
                <div class="col-md-4"><x-form.select name="province" label="Provincia" required :options="array_combine($provinces, $provinces)" :selected="$institution?->province" /></div>
                <div class="col-md-4"><x-form.input name="canton" label="Cantón" required maxlength="80" :value="$institution?->canton" /></div>
                <div class="col-md-4"><x-form.input name="parish" label="Parroquia" maxlength="80" :value="$institution?->parish" /></div>
                <div class="col-md-8"><x-form.input name="address" label="Dirección" maxlength="255" :value="$institution?->address" /></div>
                <div class="col-md-4"><x-form.input name="phone" label="Teléfono institucional" maxlength="30" :value="$institution?->phone" /></div>
                <div class="col-md-8"><x-form.input name="email" type="email" label="Correo institucional" maxlength="150" :value="$institution?->email" /></div>
                <div class="col-12"><x-form.textarea name="notes" label="Observaciones internas" rows="2" maxlength="500" :value="$institution?->notes" help="Solo datos administrativos. No escriba datos de estudiantes." /></div>
            </div>
        </div></div>

        @unless ($editing)
            <div class="card"><div class="card-header"><i class="bi bi-person-gear" aria-hidden="true"></i>Administrador inicial y estado</div><div class="card-body">
                <div class="row">
                    <div class="col-md-6"><x-form.input name="admin_name" label="Nombre del administrador" required maxlength="255" /></div>
                    <div class="col-md-6"><x-form.input name="admin_email" type="email" label="Correo del administrador" required maxlength="255" /></div>
                    <div class="col-md-6"><x-form.select name="status" label="Estado inicial" required :placeholder="false"
                        :options="['en_preparacion' => 'En preparación (sin página pública)', 'activa' => 'Activa']" selected="en_preparacion" /></div>
                </div>
            </div></div>
        @endunless

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i>{{ $editing ? 'Guardar cambios' : 'Registrar institución' }}</button>
            <a href="{{ $editing ? route('platform.institutions.show', $institution) : route('platform.institutions.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
