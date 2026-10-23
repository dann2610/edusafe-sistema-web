<x-layouts.app title="Curso" :heading="$course ? 'Editar curso' : 'Nuevo curso'"
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Cursos' => route('admin.cursos.index'), ($course ? 'Editar' : 'Nuevo') => null]">
    <div class="card"><div class="card-body p-4">
        <form method="POST" novalidate action="{{ $course ? route('admin.cursos.update', $course) : route('admin.cursos.store') }}">
            @csrf @if ($course) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6"><x-form.select name="academic_period_id" label="Periodo lectivo" :options="$periods" :selected="$course?->academic_period_id ?? $defaultPeriod" required /></div>
                <div class="col-md-6"><x-form.input name="level" label="Nivel" :value="$course?->level" required maxlength="60" help="Ejemplo: 8.º EGB, 1.º BGU." /></div>
                <div class="col-md-3"><x-form.input name="parallel" label="Paralelo" :value="$course?->parallel" required maxlength="5" /></div>
                <div class="col-md-3"><x-form.select name="shift" label="Jornada" :options="$shifts" :selected="$course?->shift ?? 'matutina'" required :placeholder="false" /></div>
                <div class="col-md-6"><x-form.select name="tutor_user_id" label="Docente tutor" :options="$tutors" :selected="$course?->tutor_user_id" placeholder="Sin tutor asignado" /></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Guardar</button>
                <a href="{{ route('admin.cursos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div></div>
</x-layouts.app>
