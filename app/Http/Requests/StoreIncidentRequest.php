<?php

namespace App\Http\Requests;

use App\Enums\ParticipantRole;
use App\Models\AcademicPeriod;
use App\Services\AttachmentStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('incidentes.registrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Descarta filas de involucrados completamente vacías
        $participants = collect($this->input('participants', []))
            ->filter(fn ($p) => is_array($p) && (filled($p['student_id'] ?? null) || filled($p['non_student_description'] ?? null) || filled($p['notes'] ?? null)))
            ->values()->all();
        $this->merge(['participants' => $participants]);
    }

    public function rules(): array
    {
        $current = AcademicPeriod::current();

        return [
            'incident_category_id' => ['required', 'integer', Rule::exists('incident_categories', 'id')->where('is_active', true)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('academic_period_id', $current?->id ?? 0)],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'immediate_risk' => ['nullable', 'boolean'],
            'participants' => ['array', 'max:10'],
            'participants.*.student_id' => ['nullable', 'integer', Rule::exists('students', 'id')->where('is_active', true)],
            'participants.*.non_student_description' => ['nullable', 'string', 'max:150'],
            'participants.*.role' => ['required', Rule::enum(ParticipantRole::class)],
            'participants.*.notes' => ['nullable', 'string', 'max:500'],
        ] + AttachmentStorage::validationRules();
    }

    public function attributes(): array
    {
        return [
            'incident_category_id' => 'la categoría',
            'participants.*.student_id' => 'el estudiante',
            'participants.*.non_student_description' => 'la descripción de la persona',
            'participants.*.role' => 'el rol del involucrado',
            'participants.*.notes' => 'las observaciones del involucrado',
            'attachments' => 'las evidencias',
            'attachments.*' => 'la evidencia',
        ];
    }

    public function messages(): array
    {
        return ['description.min' => 'Describa lo ocurrido con al menos 20 caracteres.'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $seen = [];
            foreach ($this->input('participants', []) as $i => $p) {
                $n = $i + 1;
                if (blank($p['student_id'] ?? null) && blank($p['non_student_description'] ?? null)) {
                    $validator->errors()->add("participants.$i.student_id", "Involucrado {$n}: seleccione un estudiante o describa a la persona.");
                }
                $key = ($p['student_id'] ?? '').'|'.($p['role'] ?? '');
                if (filled($p['student_id'] ?? null) && isset($seen[$key])) {
                    $validator->errors()->add("participants.$i.student_id", "Involucrado {$n}: el estudiante está repetido con el mismo rol.");
                }
                $seen[$key] = true;
            }
        }];
    }
}
