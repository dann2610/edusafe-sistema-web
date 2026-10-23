{{-- Fila de involucrado. $i es el índice (o __INDEX__ en la plantilla). --}}
@php
    $err = fn ($f) => is_numeric($i) ? $errors->first("participants.$i.$f") : null;
    $studentsByCourse = $studentsByCourse ?? [];
@endphp
<div class="participant-row border rounded p-3 mb-2" data-row>
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small" for="p-{{ $i }}-student">Estudiante</label>
            <select class="form-select form-select-sm @if ($err('student_id')) is-invalid @endif" id="p-{{ $i }}-student" name="participants[{{ $i }}][student_id]">
                <option value="">— No es estudiante / no se sabe —</option>
                @foreach ($studentsByCourse as $course => $students)
                    <optgroup label="{{ $course }}">
                        @foreach ($students as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($p['student_id'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @if ($err('student_id'))<div class="invalid-feedback">{{ $err('student_id') }}</div>@endif
        </div>
        <div class="col-md-3">
            <label class="form-label small" for="p-{{ $i }}-desc">O descripción general</label>
            <input type="text" class="form-control form-control-sm" id="p-{{ $i }}-desc" name="participants[{{ $i }}][non_student_description]"
                   value="{{ $p['non_student_description'] ?? '' }}" maxlength="150" placeholder="Ej.: persona adulta externa">
        </div>
        <div class="col-md-2">
            <label class="form-label small" for="p-{{ $i }}-role">Rol</label>
            <select class="form-select form-select-sm @if ($err('role')) is-invalid @endif" id="p-{{ $i }}-role" name="participants[{{ $i }}][role]">
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($p['role'] ?? 'afectado') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small" for="p-{{ $i }}-notes">Observación</label>
            <input type="text" class="form-control form-control-sm" id="p-{{ $i }}-notes" name="participants[{{ $i }}][notes]" value="{{ $p['notes'] ?? '' }}" maxlength="500">
        </div>
        <div class="col-md-1 text-md-end">
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row aria-label="Quitar involucrado"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </div>
    </div>
</div>
