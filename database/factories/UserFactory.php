<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'institution_id' => fn () => Institution::default()?->id,
            'name' => fake()->name(),
            'email' => 'u'.Str::lower(Str::random(10)).'@uecrc.test',
            'email_verified_at' => now(),
            'password' => static::$password ??= 'Prueba.Segura-123',
            'remember_token' => Str::random(10),
        ];
    }

    /** Asigna un rol existente (los roles se crean con RolesAndPermissionsSeeder). */
    public function role(RoleSlug $role): static
    {
        return $this->afterMaking(function (User $user) use ($role) {
            $user->role_id = Role::where('slug', $role->value)->value('id');
            if ($role === RoleSlug::Superadmin) {
                $user->institution_id = null;
            }
        });
    }

    public function forInstitution(Institution $institution): static
    {
        return $this->state(fn () => ['institution_id' => $institution->id]);
    }

    public function inactive(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->is_active = false;
            $user->deactivated_at = now();
        });
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
