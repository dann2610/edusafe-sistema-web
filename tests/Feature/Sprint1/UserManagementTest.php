<?php

namespace Tests\Feature\Sprint1;

use App\Enums\RoleSlug;
use App\Models\ExternalEntity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->role(RoleSlug::Administrador)->create(['email' => 'admin@uecrc.test']);
    }

    private function roleId(RoleSlug $role): int
    {
        return Role::where('slug', $role->value)->value('id');
    }

    public function test_el_listado_permite_buscar_y_filtrar(): void
    {
        User::factory()->role(RoleSlug::Docente)->create(['name' => 'María Docente', 'email' => 'maria@uecrc.test']);
        User::factory()->role(RoleSlug::Dece)->create(['name' => 'Pedro Consejero', 'email' => 'pedro@uecrc.test']);

        $this->actingAs($this->admin);
        $this->get('/admin/usuarios?q=maría')->assertOk()->assertSee('maria@uecrc.test')->assertDontSee('pedro@uecrc.test');
        $this->get('/admin/usuarios?rol=dece')->assertOk()->assertSee('pedro@uecrc.test')->assertDontSee('maria@uecrc.test');
        $this->get('/admin/usuarios?rol=inventado')->assertSessionHasErrors('rol');
    }

    public function test_crea_un_usuario_con_contrasena_temporal_hasheada(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/usuarios', [
            'name' => 'Nueva Docente',
            'email' => 'Nueva.Docente@UECRC.test',
            'position' => 'Docente tutora',
            'role_id' => $this->roleId(RoleSlug::Docente),
        ]);

        $user = User::where('email', 'nueva.docente@uecrc.test')->firstOrFail();
        $response->assertRedirect(route('admin.usuarios.show', $user));
        $temporary = session('temporary_password');

        $this->assertNotEmpty($temporary);
        $this->assertTrue(Hash::check($temporary, $user->password));
        $this->assertNotSame($temporary, $user->getRawOriginal('password'));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole(RoleSlug::Docente));
        $this->assertDatabaseHas('audit_logs', ['action' => 'usuario_creado', 'auditable_id' => $user->id, 'user_id' => $this->admin->id]);
        $this->assertStringNotContainsString($temporary, DB::table('audit_logs')->get()->toJson());

        // La contraseña temporal se muestra una sola vez
        $this->get(route('admin.usuarios.show', $user))->assertOk()->assertSee($temporary);
        $this->get(route('admin.usuarios.show', $user))->assertOk()->assertDontSee($temporary);
    }

    public function test_valida_campos_obligatorios_y_correo_unico_en_espanol(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/usuarios', [])->assertSessionHasErrors([
            'name' => 'El nombre es obligatorio.',
            'email' => 'El correo electrónico es obligatorio.',
            'role_id' => 'El rol es obligatorio.',
        ]);

        $this->post('/admin/usuarios', ['name' => 'Copia', 'email' => 'ADMIN@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Docente)])
            ->assertSessionHasErrors(['email' => 'El valor de el correo electrónico ya está registrado.']);
    }

    public function test_el_rol_policial_exige_entidad_externa_y_otros_roles_la_descartan(): void
    {
        $this->actingAs($this->admin);
        $entity = ExternalEntity::where('type', 'policia')->firstOrFail();

        $this->post('/admin/usuarios', ['name' => 'Agente', 'email' => 'agente@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Policia)])
            ->assertSessionHasErrors(['external_entity_id' => 'Debe vincular la cuenta policial a una entidad externa.']);

        $this->post('/admin/usuarios', ['name' => 'Agente', 'email' => 'agente@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Policia), 'external_entity_id' => $entity->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($entity->id, User::findByEmail('agente@uecrc.test')->external_entity_id);

        // Una cuenta policial no puede vincularse a una entidad no policial
        $health = ExternalEntity::where('type', 'salud')->firstOrFail();
        $this->post('/admin/usuarios', ['name' => 'Agente Dos', 'email' => 'agente2@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Policia), 'external_entity_id' => $health->id])
            ->assertSessionHasErrors('external_entity_id');

        $this->post('/admin/usuarios', ['name' => 'Docente', 'email' => 'doc@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Docente), 'external_entity_id' => $entity->id])
            ->assertSessionHasNoErrors();
        $this->assertNull(User::findByEmail('doc@uecrc.test')->external_entity_id);
    }

    public function test_no_se_pueden_asignar_campos_protegidos_por_asignacion_masiva(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/usuarios', [
            'name' => 'Persona', 'email' => 'persona@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Docente),
            'is_active' => false, 'must_change_password' => false, 'password' => 'Elegida.Por-Mi1',
        ]);
        $user = User::findByEmail('persona@uecrc.test');
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertFalse(Hash::check('Elegida.Por-Mi1', $user->password));
    }

    public function test_actualiza_datos_y_rol_cerrando_sus_sesiones(): void
    {
        $target = User::factory()->role(RoleSlug::Docente)->create();
        DB::table('sessions')->insert(['id' => 'sesion-x', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->admin)->put("/admin/usuarios/{$target->id}", [
            'name' => 'Nombre Corregido', 'email' => $target->email, 'role_id' => $this->roleId(RoleSlug::Dece),
        ])->assertRedirect(route('admin.usuarios.show', $target));

        $target->refresh();
        $this->assertSame('Nombre Corregido', $target->name);
        $this->assertTrue($target->hasRole(RoleSlug::Dece));
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-x']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'usuario_rol_cambiado', 'auditable_id' => $target->id]);
    }

    public function test_el_administrador_no_puede_cambiar_su_propio_rol(): void
    {
        $this->actingAs($this->admin)->put("/admin/usuarios/{$this->admin->id}", [
            'name' => 'Admin', 'email' => 'admin@uecrc.test', 'role_id' => $this->roleId(RoleSlug::Dece),
        ]);
        $this->assertTrue($this->admin->fresh()->hasRole(RoleSlug::Administrador));
    }

    public function test_desactivar_exige_motivo_cierra_sesiones_y_se_audita(): void
    {
        $target = User::factory()->role(RoleSlug::Docente)->create();
        DB::table('sessions')->insert(['id' => 'sesion-y', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->admin);

        $this->patch("/admin/usuarios/{$target->id}/desactivar", [])->assertSessionHasErrors(['reason' => 'El motivo es obligatorio.']);
        $this->assertTrue($target->fresh()->is_active);

        $this->patch("/admin/usuarios/{$target->id}/desactivar", ['reason' => 'Fin de contrato'])->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);
        $this->assertNotNull($target->fresh()->deactivated_at);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesion-y']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'usuario_desactivado', 'auditable_id' => $target->id]);

        $this->patch("/admin/usuarios/{$target->id}/activar")->assertRedirect();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_no_puede_desactivarse_a_si_mismo_ni_al_ultimo_administrador(): void
    {
        $this->actingAs($this->admin);
        $this->patch("/admin/usuarios/{$this->admin->id}/desactivar", ['reason' => 'Prueba de bloqueo'])->assertForbidden();
        $this->assertTrue($this->admin->fresh()->is_active);

        $this->assertTrue($this->admin->isLastActiveAdministrator());
        $second = User::factory()->role(RoleSlug::Administrador)->create();
        $this->assertFalse($this->admin->fresh()->isLastActiveAdministrator());
        $this->patch("/admin/usuarios/{$second->id}/desactivar", ['reason' => 'Ya no trabaja aquí'])->assertRedirect();
        $this->assertTrue($this->admin->fresh()->isLastActiveAdministrator());
    }

    public function test_genera_contrasena_temporal_nueva_y_obliga_a_cambiarla(): void
    {
        $target = User::factory()->role(RoleSlug::Docente)->create();

        $this->actingAs($this->admin)->post("/admin/usuarios/{$target->id}/contrasena-temporal")->assertRedirect();

        $temporary = session('temporary_password');
        $target->refresh();
        $this->assertTrue(Hash::check($temporary, $target->password));
        $this->assertTrue($target->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'usuario_contrasena_temporal', 'auditable_id' => $target->id]);
    }

    public function test_el_administrador_tecnico_no_tiene_acceso_a_expedientes(): void
    {
        $this->assertFalse($this->admin->can('casos.ver_todos'));
        $this->assertFalse($this->admin->can('casos.bandeja'));
        $this->assertFalse($this->admin->can('reportes.exportar_identificable'));
    }
}
