<x-layouts.app title="Mi institución" heading="Configuración de la institución" :lead="$institution->name"
                :breadcrumbs="['Configuración' => null]">
    @error('module')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
    <div class="row g-4">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('admin.institution.update') }}" novalidate class="card">
                @csrf @method('PUT')
                <div class="card-header"><i class="bi bi-building-gear" aria-hidden="true"></i>Datos y reglas</div>
                <div class="card-body">
                    <dl class="row kv small mb-3">
                        <dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">{{ $institution->name }}</dd>
                        <dt class="col-sm-4">Siglas · AMIE</dt><dd class="col-sm-8">{{ $institution->code_prefix }} · {{ $institution->amie_code ?: 'sin registrar' }}</dd>
                        <dt class="col-sm-4">Ubicación</dt><dd class="col-sm-8">{{ $institution->location() ?: '—' }}</dd>
                    </dl>
                    <p class="form-text mt-n2 mb-3">El nombre, las siglas y la ubicación los mantiene la plataforma.</p>
                    <div class="row">
                        <div class="col-12"><x-form.input name="address" label="Dirección" maxlength="255" :value="$institution->address" /></div>
                        <div class="col-md-6"><x-form.input name="phone" label="Teléfono institucional" maxlength="30" :value="$institution->phone" /></div>
                        <div class="col-md-6"><x-form.input name="email" type="email" label="Correo institucional" maxlength="150" :value="$institution->email" /></div>
                    </div>

                    <h2 class="section-title mt-2">Formulario público</h2>
                    <x-form.checkbox name="allow_reports_without_contact" :checked="$institution->allow_reports_without_contact" label="Permitir reportes sin datos de contacto"
                                     help="Quien reporta podrá no dejar nombre ni contacto. El canal sigue registrando datos técnicos de seguridad, por lo que no es anónimo por completo." />
                    <div class="col-md-6"><x-form.input name="public_report_hourly_limit" type="number" min="1" max="100" required label="Reportes por hora desde una misma conexión"
                                     :value="$institution->public_report_hourly_limit" help="Protege el canal contra envíos masivos." /></div>

                    <h2 class="section-title mt-2">Requisitos para cerrar un caso</h2>
                    <x-form.checkbox name="close_requires_followup" :checked="$institution->close_requires_followup" label="Exigir al menos una actuación registrada" />
                    <x-form.checkbox name="close_requires_measures_resolved" :checked="$institution->close_requires_measures_resolved" label="Exigir que no queden medidas pendientes o en curso" />
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i>Guardar configuración</button></div>
            </form>
        </div>
        <div class="col-xl-5">
            <div class="card" id="modulos">
                <div class="card-header"><i class="bi bi-grid" aria-hidden="true"></i>Módulos</div>
                <div class="card-body pb-0"><p class="small text-secondary">Solo puede activar los módulos que la plataforma autorizó para su institución.</p></div>
                @forelse ($modules as $m)
                    <div class="module-row d-flex align-items-center gap-3 px-3 py-3">
                        <div class="me-auto">
                            <div class="fw-semibold">{{ $m->module->name }}
                                @if (! $m->module->is_implemented)<span class="badge text-bg-secondary ms-1">Próximamente</span>
                                @elseif ($m->effective)<span class="badge text-bg-success ms-1">Activo</span>
                                @elseif ($m->enabled)<span class="badge text-bg-warning ms-1">Bloqueado</span>
                                @else<span class="badge text-bg-light ms-1">Apagado</span>@endif
                            </div>
                            <div class="small text-secondary">{{ $m->module->description }}</div>
                            @if ($m->requires)<div class="small text-secondary">Requiere: {{ implode(', ', $m->requires) }}</div>@endif
                        </div>
                        @if ($m->module->is_implemented && $m->authorized)
                            <form method="POST" action="{{ route('admin.institution.modules', $m->module->key) }}"
                                  data-confirm="{{ $m->enabled ? 'Sus pantallas y acciones dejarán de estar disponibles. Los datos ya registrados se conservan.' : 'El módulo quedará disponible para los roles que tengan permiso.' }}"
                                  data-confirm-title="{{ $m->enabled ? 'Desactivar módulo' : 'Activar módulo' }}" data-confirm-ok="{{ $m->enabled ? 'Desactivar' : 'Activar' }}" @if ($m->enabled) data-confirm-tone="danger" @endif>
                                @csrf @method('PATCH')
                                <input type="hidden" name="enabled" value="{{ $m->enabled ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $m->enabled ? 'btn-outline-secondary' : 'btn-outline-primary' }}">{{ $m->enabled ? 'Desactivar' : 'Activar' }}<span class="visually-hidden"> {{ $m->module->name }}</span></button>
                            </form>
                        @endif
                    </div>
                @empty
                    <x-empty icon="bi-grid" text="La plataforma aún no autorizó módulos." />
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
