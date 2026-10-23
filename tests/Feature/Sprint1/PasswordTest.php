<?php

namespace Tests\Feature\Sprint1;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(array $attrs = []): User
    {
        return User::factory()->role(RoleSlug::Dece)->create($attrs + ['email' => 'dece@uecrc.test']);
    }

    public function test_la_solicitud_de_recuperacion_envia_el_enlace_a_cuentas_activas(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->post('/recuperar-contrasena', ['email' => 'dece@uecrc.test'])
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_la_respuesta_es_identica_para_correos_inexistentes_y_cuentas_inactivas(): void
    {
        Notification::fake();
        $inactive = $this->user(['email' => 'inactiva@uecrc.test']);
        User::whereKey($inactive->id)->update(['is_active' => false]);

        $this->post('/recuperar-contrasena', ['email' => 'noexiste@uecrc.test'])->assertSessionHas('status', __('passwords.sent'));
        $this->post('/recuperar-contrasena', ['email' => 'inactiva@uecrc.test'])->assertSessionHas('status', __('passwords.sent'));

        Notification::assertNothingSent();
    }

    public function test_restablecer_con_token_valido_cambia_la_contrasena(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->post('/recuperar-contrasena', ['email' => 'dece@uecrc.test']);

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get('/restablecer-contrasena/'.$token.'?email=dece@uecrc.test')->assertOk()->assertSee('Nueva contraseña');

        $this->post('/restablecer-contrasena', [
            'token' => $token,
            'email' => 'dece@uecrc.test',
            'password' => 'Nueva.Clave-2026',
            'password_confirmation' => 'Nueva.Clave-2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Nueva.Clave-2026', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'contrasena_restablecida', 'user_id' => $user->id]);
    }

    public function test_un_token_invalido_no_cambia_la_contrasena(): void
    {
        $user = $this->user();
        $this->post('/restablecer-contrasena', [
            'token' => 'token-falso', 'email' => 'dece@uecrc.test',
            'password' => 'Nueva.Clave-2026', 'password_confirmation' => 'Nueva.Clave-2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('Prueba.Segura-123', $user->fresh()->password));
    }

    public function test_la_contrasena_temporal_obliga_a_cambiarla_antes_de_continuar(): void
    {
        $user = $this->user();
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)->get('/panel')->assertRedirect(route('password.change'));
        $this->get('/cambiar-contrasena')->assertOk()->assertSee('contraseña temporal');

        $this->put('/cambiar-contrasena', [
            'current_password' => 'Prueba.Segura-123',
            'password' => 'Personal.Clave-2026',
            'password_confirmation' => 'Personal.Clave-2026',
        ])->assertRedirect(route('panel'));

        $this->assertFalse($user->fresh()->must_change_password);
        $this->get('/panel')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'contrasena_cambiada', 'user_id' => $user->id]);
    }

    public function test_el_cambio_exige_la_contrasena_actual_y_la_politica_de_seguridad(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put('/cambiar-contrasena', ['current_password' => 'mala', 'password' => 'Personal.Clave-2026', 'password_confirmation' => 'Personal.Clave-2026'])
            ->assertSessionHasErrors(['current_password' => 'La contraseña actual es incorrecta.']);

        $this->put('/cambiar-contrasena', ['current_password' => 'Prueba.Segura-123', 'password' => 'corta', 'password_confirmation' => 'corta'])
            ->assertSessionHasErrors('password');

        $this->put('/cambiar-contrasena', ['current_password' => 'Prueba.Segura-123', 'password' => 'Prueba.Segura-123', 'password_confirmation' => 'Prueba.Segura-123'])
            ->assertSessionHasErrors(['password' => 'La nueva contraseña debe ser distinta de la actual.']);

        $this->assertTrue(Hash::check('Prueba.Segura-123', $user->fresh()->password));
    }
}
