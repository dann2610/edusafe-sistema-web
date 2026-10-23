<x-layouts.app title="Estudiante" :heading="$student ? 'Editar estudiante' : 'Nuevo estudiante'"
                lead="Registre solo los datos necesarios. No se solicitan cédula, dirección ni teléfono."
                :breadcrumbs="['Catálogos' => route('admin.catalogos.home'), 'Estudiantes' => route('admin.estudiantes.index'), ($student ? 'Editar' : 'Nuevo') => null]">
    <div class="card"><div class="card-body p-4">
        <form method="POST" novalidate action="{{ $student ? route('admin.estudiantes.update', $student) : route('admin.estudiantes.store') }}">
            @csrf @if ($student) @method('PUT') @endif
            <div class="row">
                <div class="col-md-4"><x-form.input name="institutional_code" label="Código institucional" :value="$student?->institutional_code" required maxlength="30" /></div>
                <div class="col-md-4"><x-form.input name="first_names" label="Nombres" :value="$student?->first_names" required maxlength="100" autocomplete="off" /></div>
                <div class="col-md-4"><x-form.input name="last_names" label="Apellidos" :value="$student?->last_names" required maxlength="100" autocomplete="off" /></div>
                <div class="col-md-6">
                    @if ($current)
                        <x-form.select name="course_id" :label="'Curso en '.$current->name" :options="$courses" :selected="$courseId" placeholder="Sin matrícula" />
                    @else
                        <p class="small text-secondary">Configure un periodo lectivo vigente para registrar la matrícula.</p>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Guardar</button>
                <a href="{{ route('admin.estudiantes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div></div>
</x-layouts.app>
