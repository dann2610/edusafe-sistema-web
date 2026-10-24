<?php

namespace App\Http\Requests\Admin;

use App\Support\ScopedRule;
use App\Enums\ExternalEntityType;
use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para crear y editar cuentas.
 * La autorización real se verifica en el controlador con UserPolicy.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.gestionar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
            'position' => $this->filled('position') ? trim((string) $this->input('position')) : null,
        ]);
    }

    public function rules(): array
    {
        /** @var User|null $target */
        $target = $this->route('user');
        $policeRoleId = Role::where('slug', RoleSlug::Policia->value)->value('id');

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required', 'string', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($target?->id),
            ],
            'position' => ['nullable', 'string', 'max:120'],
            'role_id' => [$target ? 'sometimes' : 'required', 'integer', Rule::exists('roles', 'id')->whereNot('slug', RoleSlug::Superadmin->value)],
            'external_entity_id' => [
                Rule::requiredIf(fn () => (int) $this->input('role_id', $target?->role_id) === (int) $policeRoleId),
                'nullable', 'integer',
                ScopedRule::ownOrGlobal('external_entities')->where('is_active', true)->whereIn('type', ExternalEntityType::policeTypes()),
            ],
        ];
    }
}
