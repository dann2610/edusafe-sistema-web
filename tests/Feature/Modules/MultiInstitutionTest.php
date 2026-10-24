<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\FollowUpType;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Institution;
use App\Models\Module;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CaseMessageNotification;
use App\Services\IncidentRegistrar;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCases;
use Tests\TestCase;

/**
 * Aislamiento entre instituciones, módulos, plataforma y nuevas acciones del
 * flujo (información adicional, escalamiento y requisitos de cierre).
 */
class MultiInstitutionTest extends TestCase
{
    use CreatesCases, RefreshDatabase;

    protected bool $seed = true;

    private Institution $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->other = Institution::forceCreate([
            'name' => 'Unidad Educativa Ficticia de Prueba', 'slug' => 'uefict', 'code_prefix' => 'UEFIC',
            'province' => 'Pichincha', 'canton' => 'Quito', 'status' => 'activa', 'is_default' => false,
            'allow_reports_without_contact' => false,
        ]);
        app(ModuleService::class)->provision($this->other);
        $period = new AcademicPeriod(['name' => 'Ficticio 2026', 'starts_on' => '2026-05-01', 'ends_on' => '2027-02-28', 'is_active' => true]);
        $period->institution_id = $this->other->id;
        $period->save();
        $period->markAsCurrent();
    }

    /** Acceso negado: 403 (sin permiso) o 404 (el registro no existe en su institución). */
    private function assertDenied(\Illuminate\Testing\TestResponse $response): void
    {
        $this->assertContains($response->status(), [403, 404], 'Se esperaba 403 o 404 y se obtuvo '.$response->status());
    }

    private function otherUser(RoleSlug $role): User
    {
        return User::factory()->role($role)->forInstitution($this->other)->create()->fresh();
    }

    private function otherIncident(): Incident
    {
        return app(IncidentRegistrar::class)->registerInternal($this->otherUser(RoleSlug::Docente), [
            'incident_category_id' => $this->category()->id,
            'description' => 'Caso ficticio de la otra institución con detalle suficiente.',
            'immediate_risk' => false,
        ]);
    }

    public function test_los_casos_de_otra_institucion_no_se_ven_ni_por_url(): void
    {
        $mine = $this->incident();
        $theirs = $this->otherIncident();
        $this->assertStringStartsWith('UEFIC-', $theirs->code);

        $dece = $this->user(RoleSlug::Dece);
        $this->actingAs($dece)->get('/casos')->assertOk()->assertSee($mine->code)->assertDontSee($theirs->code);
        $this->get(route('casos.show', $theirs))->assertNotFound();
        $this->post(route('casos.transition', $theirs), ['status' => 'en_revision'])->assertNotFound();
        $this->post(route('casos.assign', $theirs), ['user_id' => $dece->id, 'assignment_role' => 'principal'])->assertNotFound();
        $this->assertSame('registrado', Incident::withoutInstitutionScope()->find($theirs->id)->status->value);

        // Y en sentido contrario
        $this->actingAs($this->otherUser(RoleSlug::Dece))->get(route('casos.show', $mine))->assertNotFound();
    }

    public function test_adjuntos_de_otra_institucion_no_se_descargan(): void
    {
        Storage::fake('local');
        $theirs = app(IncidentRegistrar::class)->registerInternal($this->otherUser(RoleSlug::Docente), [
            'incident_category_id' => $this->category()->id,
            'description' => 'Caso ficticio con evidencia de la otra institución.',
        ], [], [$this->fakePdf()]);
        $attachment = $theirs->attachments()->firstOrFail();

        $this->assertDenied($this->actingAs($this->user(RoleSlug::Dece))->get(route('attachments.download', $attachment)));
    }

    public function test_no_se_pueden_asignar_usuarios_ni_estudiantes_de_otra_institucion(): void
    {
        $incident = $this->incident();
        $foreignTeacher = $this->otherUser(RoleSlug::Docente);
        $foreignStudent = Student::factory()->create(['institution_id' => $this->other->id]);

        $this->actingAs($this->user(RoleSlug::Dece));
        $this->post(route('casos.assign', $incident), ['user_id' => $foreignTeacher->id, 'assignment_role' => 'principal'])->assertSessionHasErrors('user_id');
        $this->post(route('casos.participants.store', $incident), ['student_id' => $foreignStudent->id, 'role' => 'afectado'])->assertSessionHasErrors('student_id');
        $this->assertSame(0, $incident->activeAssignments()->count());
    }

    public function test_el_administrador_solo_gestiona_usuarios_y_categorias_de_su_institucion(): void
    {
        $foreignAdmin = $this->otherUser(RoleSlug::Administrador);
        $foreignCategory = new IncidentCategory(['code' => 'LOCALX', 'name' => 'Local de otra', 'default_priority' => 'normal']);
        $foreignCategory->institution_id = $this->other->id;
        $foreignCategory->save();

        $admin = $this->user(RoleSlug::Administrador);
        $this->actingAs($admin)->get('/admin/usuarios')->assertOk()->assertDontSee($foreignAdmin->email);
        $this->assertDenied($this->get(route('admin.usuarios.show', $foreignAdmin)));
        $this->assertDenied($this->patch(route('admin.usuarios.deactivate', $foreignAdmin)));
        $this->assertTrue($foreignAdmin->fresh()->is_active);

        $this->get('/admin/categorias')->assertDontSee('Local de otra');
        $this->get("/admin/categorias/{$foreignCategory->id}")->assertNotFound();
        $this->get('/admin/estudiantes')->assertOk();
    }

    public function test_conteos_del_dashboard_y_reportes_solo_de_la_institucion(): void
    {
        $this->incident();
        $this->otherIncident();
        $this->otherIncident();

        $this->assertSame(2, Incident::withoutInstitutionScope()->where('institution_id', $this->other->id)->count());
        $this->assertSame(1, Incident::count(), 'El alcance global limita a la institución del contexto');

        $this->actingAs($this->user(RoleSlug::Autoridad))->get('/indicadores')->assertOk()
            ->assertViewHas('summary', fn ($s) => (int) $s['total'] === 1);
    }

    public function test_un_modulo_desactivado_bloquea_rutas_y_menu(): void
    {
        $admin = $this->user(RoleSlug::Administrador);
        $authority = $this->user(RoleSlug::Autoridad);
        $this->actingAs($authority)->get('/indicadores')->assertOk();

        $this->actingAs($admin)->patch(route('admin.institution.modules', 'dashboard'), ['enabled' => 0])->assertRedirect();
        app(ModuleService::class)->flush();

        $this->actingAs($authority)->get('/indicadores')->assertNotFound();
        $this->get('/panel')->assertOk()->assertDontSee(route('dashboard'));

        // No afecta a la otra institución
        $this->actingAs($this->otherUser(RoleSlug::Autoridad))->get('/indicadores')->assertOk();
    }

    public function test_dependencias_y_modulos_no_implementados(): void
    {
        $admin = $this->user(RoleSlug::Administrador);
        $this->actingAs($admin);
        // evidencias_publicas no está autorizado por defecto en nada que lo apague: requiere reporte_publico activo
        $this->patch(route('admin.institution.modules', 'reporte_publico'), ['enabled' => 0])->assertSessionHasErrors('module');
        $this->patch(route('admin.institution.modules', 'integracion_externa'), ['enabled' => 1])->assertSessionHasErrors('module');
        $this->assertFalse(app(ModuleService::class)->enabled('integracion_externa'));

        $super = $this->user(RoleSlug::Superadmin);
        $this->actingAs($super)->patch(route('platform.modules.toggle', 'integracion_externa'), ['enabled' => 1])->assertSessionHasErrors('module');
    }

    public function test_el_superadministrador_registra_instituciones_sin_duplicados(): void
    {
        $super = $this->user(RoleSlug::Superadmin);
        $this->actingAs($super)->get('/plataforma')->assertOk()->assertSee('Resumen de la plataforma');

        $data = ['name' => 'Escuela Fiscal Ficticia Número Uno', 'code_prefix' => 'efn1', 'amie_code' => '17H00001', 'province' => 'Pichincha', 'canton' => 'Quito',
            'status' => 'en_preparacion', 'admin_name' => 'Administración Ficticia', 'admin_email' => 'admin.efn1@ejemplo.test'];
        $this->post('/plataforma/instituciones', $data)->assertRedirect()->assertSessionHas('temporary_password');

        $created = Institution::where('code_prefix', 'EFN1')->firstOrFail();
        $admin = User::where('email', 'admin.efn1@ejemplo.test')->firstOrFail();
        $this->assertSame($created->id, $admin->institution_id);
        $this->assertTrue($admin->must_change_password);
        $this->assertTrue(app(ModuleService::class)->enabled('reporte_publico', $created->id));

        $this->post('/plataforma/instituciones', ['admin_email' => 'otro@ejemplo.test'] + $data)->assertSessionHasErrors(['name', 'code_prefix', 'amie_code']);
        $this->get('/plataforma/instituciones/'.$created->slug)->assertOk()->assertSee('Módulos autorizados');
        $this->get('/i/'.$created->slug)->assertNotFound(); // en preparación: sin página pública
    }

    public function test_suspender_bloquea_el_acceso_y_la_pagina_publica_sin_borrar_datos(): void
    {
        $incident = $this->otherIncident();
        $dece = $this->otherUser(RoleSlug::Dece);
        $super = $this->user(RoleSlug::Superadmin);

        $this->actingAs($super)->patch(route('platform.institutions.status', $this->other), ['status' => 'suspendida', 'reason' => 'Prueba de suspensión'])->assertRedirect();
        $this->assertSame('suspendida', $this->other->fresh()->status->value);

        $this->actingAs($dece)->get('/casos')->assertRedirect(route('login'));
        $this->get('/i/'.$this->other->slug)->assertNotFound();
        $this->assertNotNull(Incident::withoutInstitutionScope()->find($incident->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'institucion_estado_cambiado']);
    }

    public function test_el_superadministrador_no_abre_expedientes(): void
    {
        $incident = $this->incident();
        $super = $this->user(RoleSlug::Superadmin);
        $this->assertDenied($this->actingAs($super)->get(route('casos.show', $incident)));
        $this->get('/casos')->assertForbidden();
        $this->get('/admin/usuarios')->assertForbidden();
    }

    public function test_quien_registro_agrega_informacion_y_el_asignado_escala(): void
    {
        Notification::fake();
        $teacher = $this->user(RoleSlug::Docente);
        $dece = $this->user(RoleSlug::Dece);
        $incident = $this->incident($teacher);

        $this->actingAs($teacher)->post(route('reportes.notes.store', $incident), ['body' => 'corto'])->assertSessionHasErrors('body');
        $this->post(route('reportes.notes.store', $incident), ['body' => 'El estudiante volvió a recibir mensajes hoy.'])->assertRedirect();
        $this->assertSame(1, $incident->notes()->count());
        $this->assertDatabaseHas('case_events', ['incident_id' => $incident->id, 'event_type' => 'informacion_adicional']);
        $this->get(route('reportes.show', $incident))->assertSee('El estudiante volvió a recibir mensajes hoy.');

        // Escalar: solo un responsable asignado que no es DECE
        $other = $this->user(RoleSlug::Docente);
        $this->actingAs($other)->post(route('casos.escalate', $incident), ['reason' => 'Necesito apoyo del DECE con urgencia.'])->assertForbidden();
        $this->actingAs($dece)->post(route('casos.assign', $incident), ['user_id' => $other->id, 'assignment_role' => 'principal'])->assertSessionHasNoErrors();
        $this->actingAs($other)->post(route('casos.escalate', $incident), ['reason' => 'Necesito apoyo del DECE con urgencia.'])->assertRedirect();
        Notification::assertSentTo($dece, CaseMessageNotification::class);
        $this->assertDatabaseHas('case_events', ['incident_id' => $incident->id, 'event_type' => 'escalado']);
    }

    public function test_requisitos_de_cierre_configurados_por_la_institucion(): void
    {
        Institution::default()->forceFill(['close_requires_followup' => true])->save();
        $incident = $this->incident();
        $dece = $this->user(RoleSlug::Dece);
        $this->actingAs($dece)->post(route('casos.transition', $incident), ['status' => 'en_revision'])->assertSessionHasNoErrors();

        $close = ['status' => 'cerrado', 'justification' => 'Situación aclarada', 'closure_summary' => 'Se conversó con las partes.'];
        $this->post(route('casos.transition', $incident), $close)->assertSessionHasErrors('status');
        $this->assertSame('en_revision', $incident->fresh()->status->value);

        $this->post(route('casos.followups.store', $incident), [
            'follow_up_type_id' => FollowUpType::query()->value('id'),
            'performed_at' => now()->subHour()->format('Y-m-d H:i'),
            'description' => 'Entrevista ficticia con las partes involucradas.',
        ])->assertSessionHasNoErrors();
        $this->post(route('casos.transition', $incident), $close)->assertSessionHasNoErrors();
        $this->assertSame('cerrado', $incident->fresh()->status->value);
    }

    public function test_el_formulario_publico_de_cada_institucion_crea_casos_en_ella(): void
    {
        $this->get('/i/'.$this->other->slug.'/reportar')->assertOk()->assertSee($this->other->name)
            ->assertDontSee('value="sin_contacto"', false); // esta institución exige contacto

        $this->post('/i/'.$this->other->slug.'/reportar', [
            'incident_category_id' => $this->category('ACOSO')->id, 'date_unknown' => '1',
            'description' => 'Reporte ficticio enviado a la otra institución.', 'is_ongoing' => 'no', 'authority_informed' => 'no',
            'reporter_relationship' => 'representante', 'reporter_mode' => 'con_contacto', 'reporter_contact_name' => 'Ficticio', 'reporter_contact' => '0999999999',
            'privacy' => '1', 'confirm' => '1', 'form_token' => \Illuminate\Support\Facades\Crypt::encryptString((string) (time() - 30)),
        ])->assertRedirect(route('inst.report.sent', $this->other));

        $incident = Incident::withoutInstitutionScope()->latest('id')->firstOrFail();
        $this->assertSame($this->other->id, $incident->institution_id);
        $this->assertStringStartsWith('UEFIC-', $incident->code);
    }
}
