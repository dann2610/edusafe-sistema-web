<?php

namespace Tests\Feature\Sprint1;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Un usuario no puede entrar a un módulo solo porque conoce su URL:
 * cada ruta valida el permiso en el servidor.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function nonAdminRoles(): array
    {
        return [
            'docente' => [RoleSlug::Docente],
            'dece' => [RoleSlug::Dece],
            'autoridad' => [RoleSlug::Autoridad],
            'policia' => [RoleSlug::Policia],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_solo_el_administrador_tecnico_gestiona_usuarios(RoleSlug $role): void
    {
        $user = User::factory()->role($role)->create();
        $target = User::factory()->role(RoleSlug::Docente)->create();

        $this->actingAs($user);
        $this->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/usuarios/crear')->assertForbidden();
        $this->get("/admin/usuarios/{$target->id}")->assertForbidden();
        $this->get("/admin/usuarios/{$target->id}/editar")->assertForbidden();
        $this->post('/admin/usuarios', ['name' => 'Intruso', 'email' => 'x@uecrc.test', 'role_id' => 1])->assertForbidden();
        $this->patch("/admin/usuarios/{$target->id}/desactivar", ['reason' => 'intento indebido'])->assertForbidden();
        $this->post("/admin/usuarios/{$target->id}/contrasena-temporal")->assertForbidden();

        $this->assertTrue($target->fresh()->is_active);
        $this->assertDatabaseMissing('users', ['email' => 'x@uecrc.test']);
    }

    public function test_el_acceso_denegado_queda_en_la_bitacora(): void
    {
        $user = User::factory()->role(RoleSlug::Docente)->create();
        $this->actingAs($user)->get('/admin/usuarios')->assertForbidden()->assertSee('Acceso denegado');

        $this->assertDatabaseHas('audit_logs', ['action' => 'acceso_denegado', 'user_id' => $user->id, 'result' => 'denegado']);
    }

    public function test_el_menu_solo_muestra_lo_autorizado(): void
    {
        $admin = User::factory()->role(RoleSlug::Administrador)->create();
        $docente = User::factory()->role(RoleSlug::Docente)->create();

        $this->actingAs($admin)->get('/panel')->assertOk()->assertSee(route('admin.usuarios.index'));
        $this->actingAs($docente)->get('/panel')->assertOk()->assertDontSee(route('admin.usuarios.index'));
    }

    public function test_un_usuario_sin_rol_es_expulsado(): void
    {
        $user = User::factory()->create(); // sin rol
        $this->actingAs($user)->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_cada_rol_tiene_exactamente_los_permisos_del_catalogo(): void
    {
        foreach (RoleSlug::cases() as $role) {
            $user = User::factory()->role($role)->create();
            foreach (\App\Support\PermissionCatalog::MATRIX as $slug => [, , $roles]) {
                $this->assertSame(in_array($role->value, $roles, true), $user->can($slug), "{$role->value} / {$slug}");
            }
        }
    }
}
