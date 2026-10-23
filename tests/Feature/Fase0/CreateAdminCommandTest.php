<?php

namespace Tests\Feature\Fase0;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_crea_el_administrador_con_contrasena_segura_y_hasheada(): void
    {
        $this->artisan('tic:crear-admin', ['--nombre' => 'Admin Prueba', '--email' => 'Admin@UECRC.test'])
            ->expectsQuestion('Contraseña (mín. 12 caracteres, mayúsculas, minúsculas, números y símbolos)', 'Segura.Clave-2026')
            ->expectsQuestion('Confirme la contraseña', 'Segura.Clave-2026')
            ->assertSuccessful();

        $user = User::where('email', 'admin@uecrc.test')->firstOrFail();
        $this->assertTrue($user->hasRole(RoleSlug::Administrador));
        $this->assertTrue(Hash::check('Segura.Clave-2026', $user->password));
        $this->assertNotSame('Segura.Clave-2026', $user->getRawOriginal('password'));
    }

    public function test_rechaza_contrasenas_debiles(): void
    {
        $this->artisan('tic:crear-admin', ['--nombre' => 'Admin', '--email' => 'admin@uecrc.test'])
            ->expectsQuestion('Contraseña (mín. 12 caracteres, mayúsculas, minúsculas, números y símbolos)', '12345678')
            ->expectsQuestion('Confirme la contraseña', '12345678')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_rechaza_correo_duplicado(): void
    {
        User::factory()->create(['email' => 'admin@uecrc.test']);

        $this->artisan('tic:crear-admin', ['--nombre' => 'Admin', '--email' => 'ADMIN@uecrc.test'])
            ->expectsQuestion('Contraseña (mín. 12 caracteres, mayúsculas, minúsculas, números y símbolos)', 'Segura.Clave-2026')
            ->expectsQuestion('Confirme la contraseña', 'Segura.Clave-2026')
            ->assertFailed();
    }
}
