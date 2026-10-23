{{-- Campos compartidos por crear y editar usuario --}}
@php $editing = isset($user); @endphp
<div class="row">
    <div class="col-md-6">
        <x-form.input name="name" label="Nombre completo" :value="$user->name ?? null" required maxlength="255" autocomplete="off" />
    </div>
    <div class="col-md-6">
        <x-form.input name="email" type="email" label="Correo institucional" :value="$user->email ?? null" required maxlength="255" autocomplete="off" />
    </div>
    <div class="col-md-6">
        <x-form.input name="position" label="Cargo" :value="$user->position ?? null" maxlength="120"
                      help="Ejemplo: Docente tutor de 8.º EGB, Psicóloga DECE." />
    </div>
    <div class="col-md-6">
        @if (! $editing || $canChangeRole)
            <x-form.select name="role_id" label="Rol" :options="$roles->pluck('name', 'id')->all()"
                           :selected="$user->role_id ?? null" required data-police-role="{{ $policeRoleId }}"
                           help="Define qué puede ver y hacer la persona en el sistema." />
        @else
            <div class="mb-3">
                <span class="form-label d-block">Rol</span>
                <p class="form-control-plaintext py-1 mb-0">{{ $user->role?->name }}</p>
                <div class="form-text">
                    @if (auth()->user()->is($user))
                        No puede cambiar su propio rol.
                    @else
                        Es el último administrador técnico activo; su rol no puede cambiarse.
                    @endif
                </div>
                <input type="hidden" id="role_id" value="{{ $user->role_id }}" data-police-role="{{ $policeRoleId }}">
            </div>
        @endif
    </div>
    <div class="col-md-6" id="entity-group" @if ((string) old('role_id', $user->role_id ?? '') !== (string) $policeRoleId) hidden @endif>
        <x-form.select name="external_entity_id" label="Entidad externa vinculada"
                       :options="$entities->pluck('name', 'id')->all()" :selected="$user->external_entity_id ?? null"
                       help="El personal policial solo verá los casos derivados a esta entidad." />
    </div>
</div>
