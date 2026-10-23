<x-layouts.app title="Mi perfil" heading="Mi perfil" :breadcrumbs="['Mi perfil' => null]">
    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">Nombre</dt><dd class="col-sm-8">{{ $user->name }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Correo</dt><dd class="col-sm-8 text-break">{{ $user->email }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Cargo</dt><dd class="col-sm-8">{{ $user->position ?? '—' }}</dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Rol</dt><dd class="col-sm-8">{{ $user->role?->name }}</dd>
                        @if ($user->externalEntity)
                            <dt class="col-sm-4 text-secondary fw-normal">Entidad</dt><dd class="col-sm-8">{{ $user->externalEntity->name }}</dd>
                        @endif
                        <dt class="col-sm-4 text-secondary fw-normal mb-0">Último ingreso</dt>
                        <dd class="col-sm-8 mb-0">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
                <div class="card-footer bg-white d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('password.change') }}" class="btn btn-primary btn-sm"><i class="bi bi-key me-1" aria-hidden="true"></i>Cambiar contraseña</a>
                    <span class="small text-secondary">Para corregir nombre, correo o rol, comuníquese con el administrador técnico.</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
