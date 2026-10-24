<x-layouts.app :title="$institution->name" :heading="$institution->name"
                :lead="collect([$institution->code_prefix, $institution->amie_code ? 'AMIE '.$institution->amie_code : null, $institution->location()])->filter()->implode(' · ')"
                :breadcrumbs="['Plataforma' => route('platform.dashboard'), 'Instituciones' => route('platform.institutions.index'), $institution->name => null]">
    <x-slot:actions>
        <a href="{{ route('platform.institutions.edit', $institution) }}" class="btn btn-outline-primary"><i class="bi bi-pencil" aria-hidden="true"></i>Editar datos</a>
        @if ($institution->status->isPublic())
            <a href="{{ $institution->publicRoute('home') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Página pública</a>
        @endif
    </x-slot:actions>

    <x-temp-password />
    @error('module')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><span class="stat-accent" aria-hidden="true"></span>
            <span class="badge {{ $institution->status->badgeClass() }} mb-2">{{ $institution->status->label() }}</span><div class="stat-label">Estado actual</div></div></div></div>
        @foreach ([['Casos totales', $counts->total], ['Abiertos', $counts->abiertos], ['Recibidos por el canal público', $counts->publicos]] as [$l, $v])
            <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><span class="stat-accent" aria-hidden="true"></span>
                <div class="stat-value">{{ number_format((int) $v) }}</div><div class="stat-label">{{ $l }}</div></div></div></div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-7 d-grid gap-4 align-content-start">
            <div class="card" id="modulos">
                <div class="card-header"><i class="bi bi-grid" aria-hidden="true"></i>Módulos autorizados</div>
                <div class="card-body pb-0"><p class="small text-secondary">Autorizar permite que el administrador institucional active el módulo. Los módulos no implementados no pueden autorizarse.</p></div>
                @foreach ($modules as $m)
                    <div class="module-row d-flex flex-wrap align-items-center gap-3 px-3 py-3">
                        <div class="me-auto" style="min-width:220px;flex:1">
                            <div class="fw-semibold">{{ $m->module->name }}
                                @if (! $m->module->is_implemented)<span class="badge text-bg-secondary ms-1">No disponible</span>
                                @elseif ($m->effective)<span class="badge text-bg-success ms-1">En uso</span>
                                @elseif ($m->authorized)<span class="badge text-bg-light ms-1">Autorizado, apagado</span>@endif
                            </div>
                            <div class="small text-secondary">{{ $m->module->description }}</div>
                            @if ($m->requires)<div class="small text-secondary">Requiere: {{ implode(', ', $m->requires) }}</div>@endif
                        </div>
                        @if ($m->module->is_implemented)
                            <form method="POST" action="{{ route('platform.institutions.modules', [$institution, $m->module->key]) }}"
                                  data-confirm="{{ $m->authorized ? 'Al retirar la autorización el módulo se apaga en esta institución. Los datos ya registrados se conservan.' : 'El administrador institucional podrá activar este módulo.' }}"
                                  data-confirm-title="{{ $m->authorized ? 'Retirar módulo' : 'Autorizar módulo' }}" data-confirm-ok="{{ $m->authorized ? 'Retirar' : 'Autorizar' }}" @if ($m->authorized) data-confirm-tone="danger" @endif>
                                @csrf @method('PATCH')
                                <input type="hidden" name="authorized" value="{{ $m->authorized ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $m->authorized ? 'btn-outline-secondary' : 'btn-outline-primary' }}">{{ $m->authorized ? 'Retirar' : 'Autorizar' }}<span class="visually-hidden"> {{ $m->module->name }}</span></button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-xl-5 d-grid gap-4 align-content-start">
            <div class="card">
                <div class="card-header"><i class="bi bi-flag" aria-hidden="true"></i>Cambiar estado</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('platform.institutions.status', $institution) }}" novalidate
                          data-confirm="Suspender o desactivar cierra las sesiones del personal y retira la página pública. Ningún dato se elimina." data-confirm-title="Cambiar estado de la institución" data-confirm-ok="Cambiar estado">
                        @csrf @method('PATCH')
                        <x-form.select name="status" label="Nuevo estado" required :options="\App\Enums\InstitutionStatus::options()" :selected="$institution->status->value" :placeholder="false" />
                        <x-form.textarea name="reason" label="Motivo" required rows="2" maxlength="300" help="Queda en la bitácora." />
                        <button type="submit" class="btn btn-outline-primary">Guardar estado</button>
                    </form>
                    @if ($institution->suspended_at)<p class="small text-secondary mt-3 mb-0">Suspendida desde {{ $institution->suspended_at->format('d/m/Y H:i') }}.</p>@endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-person-gear" aria-hidden="true"></i>Administradores institucionales</div>
                <ul class="list-group list-group-flush">
                    @forelse ($admins as $admin)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <span><span class="fw-semibold d-block">{{ $admin->name }}</span><small class="text-secondary">{{ $admin->email }}</small></span>
                            @if ($admin->is_active)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Inactivo</span>@endif
                        </li>
                    @empty
                        <li class="list-group-item"><x-empty icon="bi-person-x" title="Sin administrador" text="Agregue uno para que la institución pueda configurarse." /></li>
                    @endforelse
                </ul>
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('platform.institutions.admins.store', $institution) }}" novalidate>
                        @csrf
                        <p class="fw-semibold mb-2">Agregar administrador</p>
                        <x-form.input name="name" label="Nombre" required maxlength="255" />
                        <x-form.input name="email" type="email" label="Correo" required maxlength="255" />
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-person-plus" aria-hidden="true"></i>Crear con contraseña temporal</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-people" aria-hidden="true"></i>Usuarios por rol</div>
                <ul class="list-group list-group-flush">
                    @forelse ($usersByRole as $rol => $n)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $rol }}</span><span class="fw-semibold tabular">{{ $n }}</span></li>
                    @empty
                        <li class="list-group-item text-secondary">Sin usuarios.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
