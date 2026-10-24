<x-layouts.app title="Módulos" heading="Módulos de la plataforma"
                lead="Deshabilitar un módulo aquí lo apaga en todas las instituciones: desaparecen sus rutas, menús y permisos. Los datos se conservan."
                :breadcrumbs="['Plataforma' => route('platform.dashboard'), 'Módulos' => null]">
    @error('module')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
    <div class="card">
        @foreach ($modules as $module)
            <div class="module-row d-flex flex-wrap align-items-center gap-3 p-3">
                <div class="me-auto" style="min-width:240px;flex:1">
                    <div class="fw-semibold">{{ $module->name }}
                        @if (! $module->is_implemented)<span class="badge text-bg-secondary ms-1">No implementado</span>
                        @elseif ($module->is_globally_enabled)<span class="badge text-bg-success ms-1">Habilitado</span>
                        @else<span class="badge text-bg-warning ms-1">Deshabilitado</span>@endif
                    </div>
                    <div class="small text-secondary">{{ $module->description }}</div>
                    <div class="small text-secondary mt-1">
                        <span class="me-3"><i class="bi bi-building" aria-hidden="true"></i> En uso en {{ $module->enabled_count }} {{ $module->enabled_count === 1 ? 'institución' : 'instituciones' }}</span>
                        @if ($module->requires)<span class="me-3">Requiere: {{ collect($module->requires)->map(fn ($k) => $names[$k] ?? $k)->implode(', ') }}</span>@endif
                        @if ($module->permissions)<span>Permisos: {{ implode(', ', $module->permissions) }}</span>@endif
                    </div>
                </div>
                @if ($module->is_implemented)
                    <form method="POST" action="{{ route('platform.modules.toggle', $module->key) }}"
                          data-confirm="{{ $module->is_globally_enabled ? 'El módulo dejará de funcionar en todas las instituciones.' : 'Las instituciones que lo tengan autorizado y activo volverán a usarlo.' }}"
                          data-confirm-title="{{ $module->is_globally_enabled ? 'Deshabilitar módulo' : 'Habilitar módulo' }}" data-confirm-ok="{{ $module->is_globally_enabled ? 'Deshabilitar' : 'Habilitar' }}" @if ($module->is_globally_enabled) data-confirm-tone="danger" @endif>
                        @csrf @method('PATCH')
                        <input type="hidden" name="enabled" value="{{ $module->is_globally_enabled ? 0 : 1 }}">
                        <button type="submit" class="btn btn-sm {{ $module->is_globally_enabled ? 'btn-outline-secondary' : 'btn-outline-primary' }}">{{ $module->is_globally_enabled ? 'Deshabilitar' : 'Habilitar' }}<span class="visually-hidden"> {{ $module->name }}</span></button>
                    </form>
                @else
                    <span class="small text-secondary">Pendiente de desarrollo</span>
                @endif
            </div>
        @endforeach
    </div>
</x-layouts.app>
