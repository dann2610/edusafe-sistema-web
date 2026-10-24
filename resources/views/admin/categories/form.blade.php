@php
    $editing = (bool) $category;
    $crumbs = $platform
        ? ['Plataforma' => route('platform.dashboard'), 'Categorías' => route($prefix.'.index'), ($editing ? $category->name : 'Nueva') => null]
        : ['Catálogos' => route('admin.catalogos.home'), 'Categorías' => route($prefix.'.index'), ($editing ? $category->name : 'Nueva') => null];
    $actionLabels = [
        'categoria_creada' => 'Categoría creada', 'categoria_actualizada' => 'Datos actualizados',
        'categoria_activada' => 'Categoría activada', 'categoria_desactivada' => 'Categoría desactivada',
        'categoria_campo_creado' => 'Campo agregado', 'categoria_campo_actualizado' => 'Campo actualizado',
        'categoria_campo_activado' => 'Campo activado', 'categoria_campo_desactivado' => 'Campo desactivado',
    ];
@endphp
<x-layouts.app :title="$editing ? $category->name : 'Nueva categoría'" :heading="$editing ? $category->name : 'Nueva categoría'"
                :lead="$editing ? ($category->isGlobal() ? 'Categoría global' : 'Categoría de la institución').' · código '.$category->code : null"
                :breadcrumbs="$crumbs">
    @if ($editing && $editable)
        <x-slot:actions>
            <form method="POST" action="{{ route($prefix.'.toggle', $category->id) }}"
                  data-confirm="{{ $category->is_active ? 'Dejará de aparecer en los formularios. Los reportes que ya la usan se conservan sin cambios.' : 'La categoría volverá a aparecer en los formularios.' }}"
                  data-confirm-title="{{ $category->is_active ? 'Desactivar categoría' : 'Activar categoría' }}" data-confirm-ok="{{ $category->is_active ? 'Desactivar' : 'Activar' }}" @if ($category->is_active) data-confirm-tone="danger" @endif>
                @csrf @method('PATCH')
                <button type="submit" class="btn {{ $category->is_active ? 'btn-outline-danger' : 'btn-outline-primary' }}">{{ $category->is_active ? 'Desactivar' : 'Activar' }}</button>
            </form>
        </x-slot:actions>
    @endif

    @if ($editing && ! $editable)
        <div class="note mb-4"><i class="bi bi-lock" aria-hidden="true"></i><div>Esta es una categoría global definida por la plataforma. Su institución puede usarla, pero no modificarla.</div></div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7 d-grid gap-4 align-content-start">
            <form method="POST" action="{{ $editing ? route($prefix.'.update', $category->id) : route($prefix.'.store') }}" novalidate class="card">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="card-header"><i class="bi bi-tag" aria-hidden="true"></i>Datos de la categoría</div>
                <fieldset class="card-body" @disabled(! $editable)>
                    <legend class="visually-hidden">Datos de la categoría</legend>
                    <div class="row">
                        <div class="col-md-8"><x-form.input name="name" label="Nombre" required maxlength="120" :value="$category?->name" /></div>
                        <div class="col-md-4">
                            @if ($editing)
                                <input type="hidden" name="code" value="{{ $category->code }}">
                                <div class="mb-3"><span class="form-label d-block">Código</span><span class="code-display fs-6">{{ $category->code }}</span></div>
                            @else
                                <x-form.input name="code" label="Código" required maxlength="30" help="Mayúsculas, números o guion bajo. No se puede cambiar." />
                            @endif
                        </div>
                        <div class="col-12"><x-form.textarea name="description" label="Descripción" rows="2" maxlength="500" :value="$category?->description" help="Se muestra en el formulario público para ayudar a elegir." /></div>
                        <div class="col-md-6"><x-form.select name="default_priority" label="Prioridad inicial" required :placeholder="false" :options="$priorities" :selected="$category?->default_priority?->value ?? 'normal'"
                                                             help="Si quien reporta indica riesgo inmediato, la prioridad pasa a urgente." /></div>
                        <div class="col-md-6"><x-form.input name="sort_order" type="number" min="0" max="9999" label="Orden" :value="$category?->sort_order ?? 100" /></div>
                        @unless ($platform)
                            <div class="col-md-12"><x-form.select name="default_assignee_user_id" label="Responsable inicial" :options="$assignees" placeholder="Sin responsable automático (lo asigna el DECE)"
                                                                 :selected="$category?->default_assignee_user_id" help="Si se define, los nuevos reportes de esta categoría se le asignan automáticamente." /></div>
                        @endunless
                        <div class="col-12"><x-form.checkbox name="available_in_public_form" :checked="$category?->available_in_public_form ?? true" label="Disponible en el formulario público" /></div>
                    </div>
                </fieldset>
                @if ($editable)
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i>{{ $editing ? 'Guardar cambios' : 'Crear categoría' }}</button>
                        <a href="{{ route($prefix.'.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                @endif
            </form>

            @if ($editing)
                <div class="card" id="campos">
                    <div class="card-header"><i class="bi bi-ui-checks" aria-hidden="true"></i>Campos adicionales</div>
                    <div class="card-body pb-2"><p class="small text-secondary mb-0">Preguntas extra que se muestran al elegir esta categoría. Solo se admiten tipos de dato predefinidos; el contenido se valida en el servidor. Desactivar un campo no borra lo que ya se registró.</p></div>
                    @forelse ($category->fields as $field)
                        <div class="module-row px-3 py-3">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <div class="me-auto">
                                    <span class="fw-semibold">{{ $field->label }}</span>
                                    <span class="badge text-bg-light ms-1">{{ $field->type->label() }}</span>
                                    @if ($field->is_required)<span class="badge text-bg-warning ms-1">Obligatorio</span>@endif
                                    @if ($field->show_in_public_form)<span class="badge text-bg-info ms-1">Público</span>@endif
                                    @unless ($field->is_active)<span class="badge text-bg-secondary ms-1">Inactivo</span>@endunless
                                    <div class="small text-secondary">{{ $field->key }}@if ($field->options) · {{ implode(' / ', $field->options) }}@endif</div>
                                </div>
                                @if ($editable)
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#field-{{ $field->id }}" aria-expanded="false" aria-controls="field-{{ $field->id }}">Editar<span class="visually-hidden"> {{ $field->label }}</span></button>
                                    <form method="POST" action="{{ route($prefix.'.fields.toggle', [$category->id, $field->id]) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $field->is_active ? 'Desactivar' : 'Activar' }}<span class="visually-hidden"> {{ $field->label }}</span></button>
                                    </form>
                                @endif
                            </div>
                            @if ($editable)
                                <form method="POST" action="{{ route($prefix.'.fields.update', [$category->id, $field->id]) }}" class="collapse mt-3" id="field-{{ $field->id }}" novalidate>
                                    @csrf @method('PUT')
                                    <input type="hidden" name="type" value="{{ $field->type->value }}">
                                    <div class="row g-2">
                                        <div class="col-md-8"><x-form.input name="label" :id="'label-'.$field->id" label="Etiqueta" required maxlength="120" :value="$field->label" /></div>
                                        <div class="col-md-4"><x-form.input name="sort_order" :id="'order-'.$field->id" type="number" min="0" max="999" label="Orden" :value="$field->sort_order" /></div>
                                        @if ($field->type->value === 'lista')
                                            <div class="col-12"><x-form.textarea name="options_text" :id="'opts-'.$field->id" label="Opciones (una por línea)" rows="3" :value="implode(PHP_EOL, $field->options ?? [])" /></div>
                                        @endif
                                        <div class="col-12"><x-form.input name="help_text" :id="'help-'.$field->id" label="Texto de ayuda" maxlength="255" :value="$field->help_text" /></div>
                                        <div class="col-sm-6"><x-form.checkbox name="is_required" :id="'req-'.$field->id" :checked="$field->is_required" label="Obligatorio" /></div>
                                        <div class="col-sm-6"><x-form.checkbox name="show_in_public_form" :id="'pub-'.$field->id" :checked="$field->show_in_public_form" label="Mostrar en el formulario público" /></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Guardar campo</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <x-empty icon="bi-ui-checks" text="Esta categoría no tiene campos adicionales." />
                    @endforelse

                    @if ($editable)
                        <form method="POST" action="{{ route($prefix.'.fields.store', $category->id) }}" class="card-body border-top" novalidate>
                            @csrf
                            <p class="fw-semibold mb-2">Agregar campo</p>
                            <div class="row g-2">
                                <div class="col-md-6"><x-form.input name="label" id="new-label" label="Etiqueta" required maxlength="120" help="Ej.: Plataforma donde ocurrió" /></div>
                                <div class="col-md-6"><x-form.input name="key" id="new-key" label="Identificador" required maxlength="40" help="Minúsculas y guion bajo. Ej.: plataforma" /></div>
                                <div class="col-md-6"><x-form.select name="type" id="new-type" label="Tipo de dato" required :options="$fieldTypes" /></div>
                                <div class="col-md-6"><x-form.input name="sort_order" id="new-order" type="number" min="0" max="999" label="Orden" value="0" /></div>
                                <div class="col-12"><x-form.textarea name="options_text" id="new-options" label="Opciones (solo para listas, una por línea)" rows="3" /></div>
                                <div class="col-12"><x-form.input name="help_text" id="new-help" label="Texto de ayuda" maxlength="255" /></div>
                                <div class="col-sm-6"><x-form.checkbox name="is_required" id="new-req" label="Obligatorio" /></div>
                                <div class="col-sm-6"><x-form.checkbox name="show_in_public_form" id="new-pub" label="Mostrar en el formulario público" /></div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg" aria-hidden="true"></i>Agregar campo</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        @if ($editing)
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header"><i class="bi bi-clock-history" aria-hidden="true"></i>Historial de cambios</div>
                    <div class="card-body">
                        @if ($history->isEmpty())
                            <x-empty icon="bi-clock-history" text="Sin cambios registrados." />
                        @else
                            <ul class="timeline">
                                @foreach ($history as $log)
                                    <li>
                                        <div class="fw-semibold">{{ $actionLabels[$log->action] ?? $log->action }}</div>
                                        <div class="small text-secondary">{{ $log->created_at->format('d/m/Y H:i') }} · {{ $log->user?->name ?? 'Sistema' }}</div>
                                        @foreach ($log->metadata['cambios'] ?? [] as $campo => $c)
                                            <div class="small">{{ str_replace('_', ' ', $campo) }}: <span class="text-secondary">{{ $c['antes'] !== '' ? $c['antes'] : '—' }}</span> → {{ $c['despues'] !== '' ? $c['despues'] : '—' }}</div>
                                        @endforeach
                                        @if (! empty($log->metadata['campo']))<div class="small text-secondary">Campo: {{ $log->metadata['campo'] }}</div>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
