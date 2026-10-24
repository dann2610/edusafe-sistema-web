@php $heading = $item ? 'Editar '.$def['singular'] : 'Nuevo registro: '.$def['singular']; @endphp
<x-layouts.app :title="$def['title']" :heading="$heading"
                :breadcrumbs="['Catálogos' => route($prefix.'.home'), $def['title'] => route($prefix.'.index', $def['key']), ($item ? 'Editar' : 'Nuevo') => null]">
    <div class="card">
        <div class="card-body p-4">
            <form method="POST" novalidate
                  action="{{ $item ? route($prefix.'.update', [$def['key'], $item->id]) : route($prefix.'.store', $def['key']) }}">
                @csrf
                @if ($item) @method('PUT') @endif
                <div class="row">
                    @foreach ($def['fields'] as $name => $f)
                        @php $value = $item?->{$name} instanceof \BackedEnum ? $item->{$name}->value : ($item?->{$name} ?? ($f['default'] ?? null)); @endphp
                        <div class="{{ in_array($f['type'], ['textarea', 'checkbox']) ? 'col-12' : 'col-md-6' }}">
                            @switch($f['type'])
                                @case('textarea')
                                    <x-form.textarea :name="$name" :label="$f['label']" :value="$value" :required="$f['required'] ?? false" :help="$f['help'] ?? null" maxlength="{{ $f['max'] ?? 500 }}" rows="3" />
                                    @break
                                @case('select')
                                    <x-form.select :name="$name" :label="$f['label']" :options="$f['options']" :selected="$value" :required="$f['required'] ?? false" :help="$f['help'] ?? null" />
                                    @break
                                @case('checkbox')
                                    <x-form.checkbox :name="$name" :label="$f['label']" :checked="(bool) $value" :help="$f['help'] ?? null" />
                                    @break
                                @default
                                    <x-form.input :name="$name" :label="$f['label']" :type="$f['type']" :value="$value" :required="$f['required'] ?? false" :help="$f['help'] ?? null"
                                                  maxlength="{{ $f['max'] ?? 255 }}" />
                            @endswitch
                        </div>
                    @endforeach
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Guardar</button>
                    <a href="{{ route($prefix.'.index', $def['key']) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
