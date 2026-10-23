<?php

namespace App\Http\Requests;

use App\Enums\ReporterRelationship;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Formulario público. Controles contra envíos automatizados:
 * - Campo trampa (sitio_web) que una persona no ve ni completa.
 * - Token cifrado con la hora de apertura: se rechazan envíos en menos de
 *   3 segundos o con más de 2 horas.
 * - Límite de envíos por IP (middleware throttle:public-report).
 * No se aceptan archivos adjuntos por este canal.
 */
class PublicReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('seguridad_escolar.reportes_publicos.habilitados');
    }

    protected function prepareForValidation(): void
    {
        foreach (['location', 'description', 'reporter_contact_name', 'reporter_contact'] as $f) {
            if (is_string($this->input($f))) {
                $this->merge([$f => trim($this->input($f)) === '' ? null : trim($this->input($f))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'incident_category_id' => ['required', 'integer', Rule::exists('incident_categories', 'id')->where('is_active', true)->where('available_in_public_form', true)],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now', 'after:-1 year'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'immediate_risk' => ['nullable', 'boolean'],
            'reporter_relationship' => ['required', Rule::enum(ReporterRelationship::class)],
            'reporter_contact_name' => ['nullable', 'string', 'max:150'],
            'reporter_contact' => ['nullable', 'string', 'max:150'],
            'privacy' => ['accepted'],
            'sitio_web' => ['prohibited'],
            'form_token' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'incident_category_id' => 'el tipo de situación',
            'reporter_relationship' => 'su relación con la institución',
            'reporter_contact_name' => 'el nombre de contacto',
            'reporter_contact' => 'el medio de contacto',
            'privacy' => 'el aviso de privacidad',
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Describa lo ocurrido con al menos 20 caracteres.',
            'privacy.accepted' => 'Debe leer y aceptar el aviso de privacidad para enviar el reporte.',
            'sitio_web.prohibited' => 'No se pudo enviar el formulario.',
            'occurred_at.before_or_equal' => 'La fecha del hecho no puede ser futura.',
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
}
