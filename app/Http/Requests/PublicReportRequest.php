<?php

namespace App\Http\Requests;

use App\Enums\ReporterRelationship;
use App\Models\IncidentCategory;
use App\Services\ModuleService;
use App\Support\ScopedRule;
use App\Support\TenantContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Formulario público por pasos (A: incidente, B: personas, C: quien reporta,
 * D: evidencias, E: revisión). Toda regla se valida aquí, en el servidor.
 *
 * Controles contra envíos automatizados: campo trampa, token cifrado con la
 * hora de apertura (mínimo 3 s, máximo 2 h) y límite por IP e institución.
 */
class PublicReportRequest extends FormRequest
{
    public const PUBLIC_PERSON_ROLES = ['afectado', 'senalado', 'testigo'];

    public function authorize(): bool
    {
        return app(ModuleService::class)->enabled('reporte_publico');
    }

    protected function prepareForValidation(): void
    {
        $trim = fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v;
        foreach (['location', 'description', 'reporter_contact_name', 'reporter_contact', 'authority_informed_detail'] as $f) {
            $this->merge([$f => $trim($this->input($f))]);
        }
        // Personas: se descartan filas completamente vacías
        $persons = collect($this->input('persons', []))
            ->filter(fn ($p) => is_array($p) && (filled($p['description'] ?? null) || filled($p['role'] ?? null) && filled($p['description'] ?? null)))
            ->map(fn ($p) => ['role' => $p['role'] ?? null, 'description' => $trim($p['description'] ?? null)])
            ->values()->all();
        $this->merge(['persons' => $persons]);
    }

    public function rules(): array
    {
        $institution = app(TenantContext::class)->institution();
        $modules = app(ModuleService::class);
        $allowAnonymous = (bool) $institution?->allow_reports_without_contact;

        $rules = [
            // A. Incidente
            'incident_category_id' => ['required', 'integer', ScopedRule::ownOrGlobal('incident_categories')->where('is_active', true)->where('available_in_public_form', true)],
            'date_unknown' => ['nullable', 'boolean'],
            'occurred_date' => ['nullable', 'required_unless:date_unknown,1', 'date', 'before_or_equal:today', 'after:-1 year'],
            'time_unknown' => ['nullable', 'boolean'],
            'occurred_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'is_ongoing' => ['required', Rule::in(['si', 'no', 'no_sabe'])],
            'immediate_risk' => ['nullable', 'boolean'],
            'authority_informed' => ['required', Rule::in(['si', 'no', 'no_sabe'])],
            'authority_informed_detail' => ['nullable', 'string', 'max:150'],
            // B. Personas involucradas (sin datos personales obligatorios)
            'reporter_is_affected' => ['nullable', 'boolean'],
            'persons' => ['array', 'max:5'],
            'persons.*.role' => ['required', Rule::in(self::PUBLIC_PERSON_ROLES)],
            'persons.*.description' => ['nullable', 'string', 'max:150'],
            // C. Quien reporta
            'reporter_relationship' => ['required', Rule::enum(ReporterRelationship::class)],
            'reporter_mode' => ['required', Rule::in($allowAnonymous ? ['con_contacto', 'sin_contacto'] : ['con_contacto'])],
            'reporter_contact_name' => ['nullable', 'required_if:reporter_mode,con_contacto', 'string', 'max:150'],
            'reporter_contact' => ['nullable', 'required_if:reporter_mode,con_contacto', 'string', 'max:150'],
            // E. Revisión
            'privacy' => ['accepted'],
            'confirm' => ['accepted'],
            'sitio_web' => ['prohibited'],
            'form_token' => ['required', 'string'],
        ];

        // D. Evidencias: solo si el módulo está activo para la institución
        if ($modules->enabled('evidencias_publicas')) {
            $rules['attachments'] = ['nullable', 'array', 'max:2'];
            $rules['attachments.*'] = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('seguridad_escolar.adjuntos.max_kb')];
        } else {
            $rules['attachments'] = ['prohibited'];
        }

        // Campos adicionales de la categoría elegida (configuración validada en servidor)
        $category = IncidentCategory::find($this->integer('incident_category_id'));
        if ($category) {
            foreach ($category->activeFields()->where('show_in_public_form', true)->get() as $field) {
                $rules['extra.'.$field->key] = $field->rules();
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attrs = [
            'incident_category_id' => 'el tipo de situación',
            'occurred_date' => 'la fecha aproximada',
            'occurred_time' => 'la hora aproximada',
            'is_ongoing' => 'si la situación continúa',
            'authority_informed' => 'si ya se informó a una autoridad',
            'authority_informed_detail' => 'a quién se informó',
            'persons.*.role' => 'el rol de la persona',
            'persons.*.description' => 'la descripción de la persona',
            'reporter_relationship' => 'su relación con la institución',
            'reporter_mode' => 'la forma de reporte',
            'reporter_contact_name' => 'su nombre',
            'reporter_contact' => 'el medio de contacto',
            'attachments' => 'las evidencias',
            'attachments.*' => 'la evidencia',
            'privacy' => 'el aviso de privacidad',
            'confirm' => 'la confirmación',
        ];
        $category = IncidentCategory::find($this->integer('incident_category_id'));
        foreach ($category?->activeFields()->get() ?? [] as $field) {
            $attrs['extra.'.$field->key] = '«'.$field->label.'»';
        }

        return $attrs;
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Describa lo ocurrido con al menos 20 caracteres.',
            'occurred_date.required_unless' => 'Indique la fecha aproximada o marque que no la conoce.',
            'occurred_date.before_or_equal' => 'La fecha no puede ser futura.',
            'occurred_date.after' => 'Este canal recibe hechos ocurridos durante el último año.',
            'reporter_mode.in' => 'Su institución requiere un dato de contacto para recibir reportes.',
            'reporter_contact_name.required_if' => 'Escriba su nombre o elija reportar sin datos de contacto.',
            'reporter_contact.required_if' => 'Escriba un teléfono o correo, o elija reportar sin datos de contacto.',
            'privacy.accepted' => 'Debe leer y aceptar el aviso de privacidad.',
            'confirm.accepted' => 'Confirme que revisó la información antes de enviarla.',
            'attachments.prohibited' => 'Este canal no recibe archivos adjuntos.',
            'attachments.max' => 'Puede adjuntar hasta 2 archivos.',
            'attachments.*.mimes' => 'Solo se aceptan archivos PDF, JPG o PNG.',
            'sitio_web.prohibited' => 'No se pudo enviar el formulario.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            try {
                $opened = (int) Crypt::decryptString((string) $this->input('form_token'));
            } catch (DecryptException) {
                $validator->errors()->add('form_token', 'El formulario no es válido. Recargue la página e intente de nuevo.');

                return;
            }
            $elapsed = time() - $opened;
            if ($elapsed < 3 || $elapsed > 7200) {
                $validator->errors()->add('form_token', 'El formulario expiró o se envió demasiado rápido. Recargue la página e intente de nuevo.');
            }
        }];
    }

    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /** Datos normalizados para IncidentRegistrar. */
    public function incidentData(): array
    {
        $d = $this->validated();
        $occurredAt = null;
        $timeKnown = false;
        if (! $this->boolean('date_unknown') && ! empty($d['occurred_date'])) {
            $timeKnown = ! $this->boolean('time_unknown') && ! empty($d['occurred_time']);
            $occurredAt = $d['occurred_date'].' '.($timeKnown ? $d['occurred_time'] : '00:00').':00';
        }

        return [
            'incident_category_id' => $d['incident_category_id'],
            'occurred_at' => $occurredAt,
            'occurred_time_known' => $timeKnown,
            'location' => $d['location'] ?? null,
            'description' => $d['description'],
            'is_ongoing' => $d['is_ongoing'],
            'immediate_risk' => $this->boolean('immediate_risk'),
            'authority_informed' => $d['authority_informed'],
            'authority_informed_detail' => $d['authority_informed'] === 'si' ? ($d['authority_informed_detail'] ?? null) : null,
            'reporter_relationship' => $d['reporter_relationship'],
            'reporter_mode' => $d['reporter_mode'],
            'reporter_contact_name' => $d['reporter_mode'] === 'con_contacto' ? ($d['reporter_contact_name'] ?? null) : null,
            'reporter_contact' => $d['reporter_mode'] === 'con_contacto' ? ($d['reporter_contact'] ?? null) : null,
            'extra' => $d['extra'] ?? [],
        ];
    }

    /** Personas involucradas como participantes sin vínculo a registros de estudiantes. */
    public function participants(): array
    {
        $out = [];
        if ($this->boolean('reporter_is_affected')) {
            $out[] = ['role' => 'afectado', 'non_student_description' => 'La persona que reporta'];
        }
        foreach ($this->validated('persons', []) as $p) {
            $out[] = ['role' => $p['role'], 'non_student_description' => $p['description'] ?: 'Sin descripción (reporte público)'];
        }

        return $out;
    }
}
