<?php

namespace App\Console\Commands;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea el administrador técnico inicial de forma segura.
 * La contraseña se solicita de forma oculta (no queda en el historial de la terminal)
 * y se almacena con hashing (bcrypt) mediante el cast "hashed" del modelo User.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'tic:crear-admin
                            {--nombre= : Nombre completo}
                            {--email= : Correo institucional}';

    protected $description = 'Crea el usuario administrador técnico inicial (contraseña solicitada de forma oculta).';

    public function handle(): int
    {
        $role = Role::where('slug', RoleSlug::Administrador->value)->first();
        if (! $role) {
            $this->error('No existen roles. Ejecute primero: php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        $name = $this->option('nombre') ?: $this->ask('Nombre completo');
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo institucional'))));
        $password = (string) $this->secret('Contraseña (mín. 12 caracteres, mayúsculas, minúsculas, números y símbolos)');
        $confirmation = (string) $this->secret('Confirme la contraseña');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->error('Ya existe un usuario con ese correo.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password, $role) {
            $user = new User(['name' => $name, 'email' => $email, 'position' => 'Administrador técnico', 'password' => $password]);
            $user->role_id = $role->id;
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();
        });

        $this->info("Administrador técnico creado: {$email}");

        return self::SUCCESS;
    }
}
