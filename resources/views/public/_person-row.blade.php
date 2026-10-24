{{-- Persona mencionada en el reporte público. $i es el índice o __INDEX__ en la plantilla. --}}
@php
    $err = fn ($f) => is_numeric($i) ? $errors->first("persons.$i.$f") : null;
@endphp
<div class="person-row" data-row>
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small" for="person-{{ $i }}-role">Rol en la situación</label>
            <select class="form-select @if ($err('role')) is-invalid @endif" id="person-{{ $i }}-role" name="persons[{{ $i }}][role]" data-row-label>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($p['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($err('role'))<div class="invalid-feedback">{{ $err('role') }}</div>@endif
        </div>
        <div class="col-md-6">
            <label class="form-label small" for="person-{{ $i }}-desc">Descripción (sin datos personales)</label>
            <input type="text" class="form-control @if ($err('description')) is-invalid @endif" id="person-{{ $i }}-desc" name="persons[{{ $i }}][description]"
                   value="{{ $p['description'] ?? '' }}" maxlength="150" placeholder="Ej.: estudiante de 10.º A" data-row-value>
            @if ($err('description'))<div class="invalid-feedback">{{ $err('description') }}</div>@endif
        </div>
        <div class="col-md-1 text-md-end">
            <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row aria-label="Quitar esta persona"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>
</div>
