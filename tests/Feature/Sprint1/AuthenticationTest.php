<?php

namespace Tests\Feature\Sprint1;

use App\Enums\RoleSlug;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const PASSWORD = 'Prueba.Segura-123';

    private function docente(array $attrs = []): User
    {
        return User::factory()->role(RoleSlug::Docente)->create($attrs + ['email' => 'docente@uecrc.test']);
    }

    public function test_la_pantalla_de_login_se_muestra_en_espanol(): void
    {
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión')->assertSee('ECU 911')->assertSee('lang="es"', false);
    }

    public function test_un_usuario_activo_inicia_sesion_y_queda_registrado(): void
    {
        $user = $this->docente();

        $response = $this->post('/login', ['email' => 'DOCENTE@uecrc.test', 'password' => self::PASSWORD]);

        $response->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'inicio_sesion', 'user_id' => $user->id, 'result' => 'exito']);
    }

    public function test_la_sesion_se_regenera_al_iniciar_sesion(): void
    {
        $this->docente();
        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => 'docente@uecrc.test', 'password' => self::PASSWORD]);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_credenciales_incorrectas_no_inician_sesion_y_se_auditan(): void
    {
        $user = $this->docente();

        $this->from('/login')->post('/login', ['email' => 'docente@uecrc.test', 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Las credenciales no son válidas.']);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'inicio_sesion_fallido', 'user_id' => $user->id, 'result' => 'fallido']);
    }

    public function test_un_correo_inexistente_da_el_mismo_mensaje_generico(): void
    {
        $this->post('/login', ['email' => 'nadie@uecrc.test', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'Las credenciales no son válidas.']);
        $this->assertGuest();
    }

    public function test_una_cuenta_desactivada_no_puede_iniciar_sesion(): void
    {
        $this->docente(['email' => 'inactivo@uecrc.test']);
        User::where('email', 'inactivo@uecrc.test')->update(['is_active' => false]);

        $this->post('/login', ['email' => 'inactivo@uecrc.test', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'inicio_sesion_fallido', 'result' => 'denegado']);
    }

    public function test_bloqueo_tras_intentos_fallidos_repetidos(): void
    {
        config(['seguridad_escolar.login.max_intentos' => 3]);
        $this->docente();
        RateLimiter::clear('docente@uecrc.test|127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => 'docente@uecrc.test', 'password' => 'incorrecta']);
        }

        // Incluso con la contraseña correcta, queda bloqueado
        $response = $this->post('/login', ['email' => 'docente@uecrc.test', 'password' => self::PASSWORD]);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'inicio_sesion_bloqueado', 'result' => 'denegado']);
    }

    public function test_cerrar_sesion_invalida_la_sesion_y_se_audita(): void
    {
        $user = $this->docente();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'cierre_sesion', 'user_id' => $user->id]);
    }

    public function test_cerrar_sesion_exige_post_con_token_csrf(): void
    {
        $user = $this->docente();
        $this->actingAs($user)->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
    }

    public function test_un_usuario_desactivado_con_sesion_abierta_es_expulsado(): void
    {
        $user = $this->docente();
        $this->actingAs($user);
        User::whereKey($user->id)->update(['is_active' => false]);
        $user->refresh(); // en una petición real el usuario se recarga desde la base

        $this->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_las_rutas_privadas_exigen_sesion(): void
    {
        foreach (['/panel', '/perfil', '/cambiar-contrasena', '/admin/usuarios'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_un_usuario_autenticado_no_ve_el_login(): void
    {
        $this->actingAs($this->docente())->get('/login')->assertRedirect(route('panel'));
    }

    public function test_las_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString("script-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_la_bitacora_no_guarda_la_contrasena_intentada(): void
    {
        $this->docente();
        $this->post('/login', ['email' => 'docente@uecrc.test', 'password' => 'MiClaveSecreta.999']);

        $dump = AuditLog::all()->toJson();
        $this->assertStringNotContainsString('MiClaveSecreta', $dump);
    }
}
