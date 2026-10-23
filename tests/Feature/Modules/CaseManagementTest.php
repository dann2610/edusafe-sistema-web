<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleSlug;
use App\Models\ExternalEntity;
use App\Models\FollowUp;
use App\Models\FollowUpType;
use App\Models\Incident;
use App\Models\MeasureType;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseStatusChangedNotification;
use App\Notifications\FollowUpDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCases;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use CreatesCases, RefreshDatabase;

    protected bool $seed = true;

    private User $dece;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dece = $this->user(RoleSlug::Dece);
    }

    private function inReview(): Incident
    {
        $incident = $this->incident();
        $this->actingAs($this->dece)->post(route('casos.transition', $incident), ['status' => 'en_revision'])->assertSessionHasNoErrors();

        return $incident->fresh();
    }

    public function test_la_bandeja_prioriza_urgentes_y_filtra(): void
    {
        $normal = $this->incident();
        $urgent = $this->incident(null, ['immediate_risk' => true]);

        $response = $this->actingAs($this->dece)->get('/casos')->assertOk();
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, $normal->code), strpos($html, $urgent->code));

        $this->get('/casos?prioridad=urgente')->assertSee($urgent->code)->assertDontSee($normal->code);
        $this->get('/casos?q='.$normal->code)->assertSee($normal->code)->assertDontSee($urgent->code);
        $this->get('/casos?estado=cerrado')->assertDontSee($normal->code);
    }

    public function test_las_transiciones_respetan_las_reglas_de_negocio(): void
    {
        $incident = $this->incident();
        $this->actingAs($this->dece);

        // registrado → cerrado no está permitido
        $this->post(route('casos.transition', $incident), ['status' => 'cerrado', 'justification' => 'x', 'closure_summary' => 'x'])
            ->assertSessionHasErrors('status');
        $this->assertSame('registrado', $incident->fresh()->status->value);

        $this->post(route('casos.transition', $incident), ['status' => 'en_revision'])->assertSessionHasNoErrors();

        // cerrar exige justificación y resumen
        $this->post(route('casos.transition', $incident), ['status' => 'cerrado'])->assertSessionHasErrors('justification');
        $this->post(route('casos.transition', $incident), ['status' => 'cerrado', 'justification' => 'No corresponde'])->assertSessionHasErrors('closure_summary');

        $this->post(route('casos.transition', $incident), ['status' => 'cerrado', 'justification' => 'Situación aclarada', 'closure_summary' => 'Se verificó con las partes.'])
            ->assertSessionHasNoErrors();
        $closed = $incident->fresh();
        $this->assertSame('cerrado', $closed->status->value);
        $this->assertNotNull($closed->closed_at);

        // reapertura con justificación
        $this->post(route('casos.transition', $incident), ['status' => 'en_revision', 'justification' => 'Nuevos hechos'])->assertSessionHasNoErrors();
        $this->assertNull($incident->fresh()->closed_at);

        $this->assertSame(4, $incident->statusHistory()->count()); // registrado, revisión, cerrado, revisión
        $this->assertDatabaseHas('case_events', ['incident_id' => $incident->id, 'event_type' => 'reabierto']);
    }

    public function test_asignar_notifica_cambia_a_asignado_y_no_cierra_el_caso(): void
    {
        Notification::fake();
        $docente = $this->user(RoleSlug::Docente);
        $incident = $this->inReview();

        $this->actingAs($this->dece)->post(route('casos.assign', $incident), ['user_id' => $docente->id, 'assignment_role' => 'principal'])
            ->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertSame('asignado', $incident->status->value);
        $this->assertTrue($incident->isAssignedTo($docente));
        Notification::assertSentTo($docente, CaseAssignedNotification::class);

        // No se puede asignar dos veces ni asignar a quien no puede dar seguimiento
        $this->post(route('casos.assign', $incident), ['user_id' => $docente->id, 'assignment_role' => 'apoyo'])->assertSessionHasErrors('user_id');
        $this->post(route('casos.assign', $incident), ['user_id' => $this->user(RoleSlug::Autoridad)->id, 'assignment_role' => 'apoyo'])->assertSessionHasErrors('user_id');
    }

    public function test_el_responsable_asignado_ve_el_caso_y_registra_seguimientos_pero_no_gestiona(): void
    {
        $docente = $this->user(RoleSlug::Docente);
        $outsider = $this->user(RoleSlug::Docente);
        $incident = $this->inReview();
        $this->actingAs($this->dece)->post(route('casos.assign', $incident), ['user_id' => $docente->id, 'assignment_role' => 'principal']);

        $this->actingAs($outsider)->get(route('casos.show', $incident))->assertForbidden();

        $this->actingAs($docente)->get(route('casos.show', $incident))->assertOk()->assertSee('Registrar seguimiento')->assertDontSee('Cambiar estado');
        $this->get('/casos/asignados')->assertOk()->assertSee($incident->code);
        $this->post(route('casos.transition', $incident), ['status' => 'en_seguimiento'])->assertForbidden();
        $this->post(route('casos.measures.store', $incident), [])->assertForbidden();

        $this->post(route('casos.followups.store', $incident), [
            'follow_up_type_id' => FollowUpType::first()->id,
            'performed_at' => now()->subMinutes(5)->format('Y-m-d\TH:i'),
            'description' => 'Entrevista con el estudiante afectado.',
            'next_follow_up_on' => now()->addDays(2)->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('en_seguimiento', $incident->fresh()->status->value);
        $this->assertSame($docente->id, FollowUp::firstOrFail()->user_id);
    }

    public function test_un_responsable_liberado_pierde_el_acceso_y_queda_el_historial(): void
    {
        $docente = $this->user(RoleSlug::Docente);
        $incident = $this->inReview();
        $this->actingAs($this->dece)->post(route('casos.assign', $incident), ['user_id' => $docente->id, 'assignment_role' => 'principal']);
        $assignment = $incident->assignments()->firstOrFail();

        $this->patch(route('casos.assign.release', [$incident, $assignment]), ['reason' => 'Cambio de tutor'])->assertSessionHasNoErrors();
        $this->assertNotNull($assignment->fresh()->unassigned_at);
        $this->actingAs($docente)->get(route('casos.show', $incident))->assertForbidden();
    }

    public function test_las_correcciones_de_seguimiento_conservan_el_original(): void
    {
        $incident = $this->inReview();
        $type = FollowUpType::first()->id;
        $this->actingAs($this->dece)->post(route('casos.followups.store', $incident), [
            'follow_up_type_id' => $type, 'performed_at' => now()->subDay()->format('Y-m-d\TH:i'), 'description' => 'Texto original del seguimiento.',
        ]);
        $original = FollowUp::firstOrFail();

        $this->post(route('casos.followups.correct', [$incident, $original]), [
            'follow_up_type_id' => $type, 'performed_at' => now()->subDays(2)->format('Y-m-d\TH:i'), 'description' => 'Texto corregido del seguimiento.',
        ])->assertSessionHasErrors('correction_reason');

        $this->post(route('casos.followups.correct', [$incident, $original]), [
            'follow_up_type_id' => $type, 'performed_at' => now()->subDays(2)->format('Y-m-d\TH:i'),
            'description' => 'Texto corregido del seguimiento.', 'correction_reason' => 'La fecha era incorrecta',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Texto original del seguimiento.', $original->fresh()->description);
        $this->assertSame($original->id, FollowUp::latest('id')->first()->corrects_follow_up_id);
        $this->assertDatabaseHas('case_events', ['incident_id' => $incident->id, 'event_type' => 'correccion']);
    }

    public function test_medidas_se_registran_y_actualizan(): void
    {
        $incident = $this->inReview();
        $this->actingAs($this->dece)->post(route('casos.measures.store', $incident), [
            'measure_type_id' => MeasureType::where('kind', 'proteccion')->first()->id,
            'description' => 'Separación de espacios en el aula durante dos semanas.',
            'responsible_user_id' => $this->dece->id,
            'starts_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $measure = $incident->measures()->firstOrFail();
        $this->assertSame('pendiente', $measure->compliance_status->value);

        $this->patch(route('casos.measures.update', [$incident, $measure]), ['compliance_status' => 'cumplida'])->assertSessionHasNoErrors();
        $this->assertSame('cumplida', $measure->fresh()->compliance_status->value);
        $this->assertDatabaseHas('case_events', ['incident_id' => $incident->id, 'event_type' => 'medida_actualizada']);

        // Una medida de otro caso no puede modificarse desde esta URL
        $other = $this->inReview();
        $this->patch(route('casos.measures.update', [$other, $measure]), ['compliance_status' => 'incumplida'])->assertNotFound();
    }

    public function test_la_derivacion_pasa_a_derivado_sin_cerrar_y_la_policia_solo_ve_su_entidad(): void
    {
        $incident = $this->inReview();
        $police = ExternalEntity::where('type', 'policia')->firstOrFail();

        $this->actingAs($this->dece)->post(route('casos.referrals.store', $incident), [
            'external_entity_id' => $police->id, 'referred_on' => now()->toDateString(), 'channel' => 'oficio',
            'reason' => 'Amenazas que requieren intervención policial.', 'shared_summary' => 'Resumen compartido con la entidad.',
            'reference_number' => 'OF-001',
        ])->assertSessionHasNoErrors();

        $this->assertSame('derivado', $incident->fresh()->status->value);
        $this->assertNull($incident->fresh()->closed_at);
        $referral = Referral::firstOrFail();

        $agent = $this->user(RoleSlug::Policia);
        $this->actingAs($agent)->get('/derivaciones')->assertOk()->assertSee($incident->code);
        $this->get(route('entity.referrals.show', $referral))->assertOk()
            ->assertSee('Resumen compartido con la entidad.')
            ->assertDontSee('Descripción ficticia de prueba');   // nunca la descripción del caso
        $this->get(route('casos.show', $incident))->assertForbidden();

        $otherAgent = $this->user(RoleSlug::Policia, ['external_entity_id' => ExternalEntity::where('type', 'dinapen')->value('id')]);
        $this->actingAs($otherAgent)->get('/derivaciones')->assertOk()->assertDontSee($incident->code);
        $this->get(route('entity.referrals.show', $referral))->assertForbidden();
    }

    public function test_roles_sin_acceso_a_expedientes(): void
    {
        $incident = $this->incident();
        foreach ([RoleSlug::Administrador, RoleSlug::Autoridad, RoleSlug::Policia] as $role) {
            $this->actingAs($this->user($role))->get(route('casos.show', $incident))->assertForbidden();
            $this->post(route('casos.transition', $incident), ['status' => 'en_revision'])->assertForbidden();
        }
        $this->assertSame('registrado', $incident->fresh()->status->value);
    }

    public function test_el_expediente_registra_la_consulta_y_muestra_el_historial(): void
    {
        $incident = $this->inReview();
        $this->actingAs($this->dece)->get(route('casos.show', $incident))->assertOk()->assertSee('Historial')->assertSee('En revisión');
        $this->assertDatabaseHas('audit_logs', ['action' => 'expediente_consultado', 'auditable_id' => $incident->id, 'user_id' => $this->dece->id]);
    }

    public function test_quien_reporto_recibe_aviso_del_cambio_de_estado(): void
    {
        Notification::fake();
        $reporter = $this->user(RoleSlug::Docente);
        $incident = $this->incident($reporter);
        $this->actingAs($this->dece)->post(route('casos.transition', $incident), ['status' => 'en_revision']);

        Notification::assertSentTo($reporter, CaseStatusChangedNotification::class, function ($n) use ($reporter) {
            return str_contains($n->toArray($reporter)['url'], '/mis-reportes/');
        });
    }

    public function test_notificaciones_solo_se_marcan_leidas_por_accion_del_usuario(): void
    {
        $this->incident();
        $notification = $this->dece->notifications()->firstOrFail();

        $this->actingAs($this->dece)->get('/notificaciones')->assertOk();
        $this->assertNull($notification->fresh()->read_at);

        $this->get(route('notifications.open', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);

        // No se pueden abrir notificaciones de otra persona
        $this->actingAs($this->user(RoleSlug::Dece))->get(route('notifications.open', $notification->id))->assertNotFound();
    }

    public function test_recordatorio_de_seguimientos_pendientes(): void
    {
        Notification::fake();
        $incident = $this->inReview();
        $this->actingAs($this->dece)->post(route('casos.followups.store', $incident), [
            'follow_up_type_id' => FollowUpType::first()->id, 'performed_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'description' => 'Seguimiento con fecha prevista.', 'next_follow_up_on' => now()->toDateString(),
        ]);

        $this->artisan('tic:recordatorios-seguimiento')->assertSuccessful();
        Notification::assertSentTo($this->dece, FollowUpDueNotification::class);
    }

    public function test_no_se_registran_actuaciones_en_casos_cerrados(): void
    {
        $incident = $this->inReview();
        $this->actingAs($this->dece)->post(route('casos.transition', $incident), ['status' => 'cerrado', 'justification' => 'No procede', 'closure_summary' => 'Resumen']);
        $this->post(route('casos.followups.store', $incident), [
            'follow_up_type_id' => FollowUpType::first()->id, 'performed_at' => now()->format('Y-m-d\TH:i'), 'description' => 'Intento posterior al cierre.',
        ])->assertSessionHasErrors('status');
    }
}
