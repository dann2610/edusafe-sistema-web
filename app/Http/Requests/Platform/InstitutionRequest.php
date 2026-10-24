<?php

namespace App\Http\Requests\Platform;

use App\Enums\InstitutionStatus;
use App\Models\Institution;
use App\Support\Ecuador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plataforma.instituciones') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code_prefix' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('code_prefix'))),
            'amie_code' => $this->filled('amie_code') ? strtoupper(trim((string) $this->input('amie_code'))) : null,
            'admin_email' => $this->filled('admin_email') ? mb_strtolower(trim((string) $this->input('admin_email'))) : null,
        ]);
    }

    public function rules(): array
    {
        /** @var Institution|null $institution */
        $institution = $this->route('institution');
        $creating = $institution === null;

        return [
            'name' => ['required', 'string', 'min:5', 'max:200', Rule::unique('institutions', 'name')->ignore($institution?->id)],
            'code_prefix' => $creating ? ['required', 'string', 'regex:/^[A-Z0-9]{2,12}$/', Rule::unique('institutions', 'code_prefix')] : ['prohibited'],
            'amie_code' => ['nullable', 'string', 'regex:/^[0-9A-Z]{6,20}$/', Rule::unique('institutions', 'amie_code')->ignore($institution?->id)],
            'province' => ['required', Rule::in(Ecuador::PROVINCES)],
            'canton' => ['required', 'string', 'max:80'],
            'parish' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +()\-]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => $creating ? ['required', Rule::in([InstitutionStatus::EnPreparacion->value, InstitutionStatus::Activa->value])] : ['prohibited'],
            'admin_name' => $creating ? ['required', 'string', 'min:3', 'max:255'] : ['prohibited'],
            'admin_email' => $creating ? ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')] : ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'el nombre de la institución', 'code_prefix' => 'las siglas', 'amie_code' => 'el código AMIE',
            'province' => 'la provincia', 'canton' => 'el cantón', 'parish' => 'la parroquia', 'address' => 'la dirección',
            'phone' => 'el teléfono', 'email' => 'el correo institucional', 'status' => 'el estado inicial',
            'admin_name' => 'el nombre del administrador', 'admin_email' => 'el correo del administrador',
        ];
    }

    public function messages(): array
    {
        return [
            'code_prefix.regex' => 'Las siglas deben tener entre 2 y 12 letras mayúsculas o números (se usan en los códigos de caso).',
            'amie_code.regex' => 'El código AMIE debe tener entre 6 y 20 letras o números.',
            'name.unique' => 'Ya existe una institución registrada con ese nombre.',
            'code_prefix.unique' => 'Esas siglas ya las usa otra institución.',
            'amie_code.unique' => 'Ese código AMIE ya está registrado.',
        ];
    }
}
