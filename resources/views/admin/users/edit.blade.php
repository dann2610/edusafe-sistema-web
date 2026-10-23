<x-layouts.app title="Editar usuario" heading="Editar usuario" :lead="$user->email"
                :breadcrumbs="['Usuarios' => route('admin.usuarios.index'), $user->name => route('admin.usuarios.show', $user), 'Editar' => null]">
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.usuarios.update', $user) }}" novalidate>
                @csrf
                @method('PUT')
                @include('admin.users._form')
                <p class="small text-secondary">Si cambia el rol, las sesiones abiertas de la persona se cerrarán para aplicar los nuevos permisos.</p>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Guardar cambios</button>
                    <a href="{{ route('admin.usuarios.show', $user) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
