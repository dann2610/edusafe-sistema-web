<x-layouts.app title="Nuevo usuario" heading="Nuevo usuario"
                lead="Se generará una contraseña temporal que la persona deberá cambiar en su primer ingreso."
                :breadcrumbs="['Usuarios' => route('admin.usuarios.index'), 'Nuevo' => null]">
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.usuarios.store') }}" novalidate>
                @csrf
                @include('admin.users._form')
                <p class="small text-secondary"><span class="required-mark">*</span> Campos obligatorios.</p>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Crear cuenta</button>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
