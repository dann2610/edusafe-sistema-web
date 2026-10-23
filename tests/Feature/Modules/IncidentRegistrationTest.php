<?php

namespace Tests\Feature\Modules;

use App\Enums\CasePriority;
use App\Enums\RoleSlug;
use App\Models\Attachment;
use App\Models\Incident;
use App\Notifications\IncidentReportedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCases;
use Tests\TestCase;

class IncidentRegistrationTest extends TestCase
{
    use CreatesCases, RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_un_docente_registra_un_incidente_completo_en_postgresql(): void
    {
        Notification::fake();
        $docente = $this->user(RoleSlug::Docente);
        $dece = $this->user(RoleSlug::Dece);
        $student = $this->enrolledStudent();

        $response = $this->actingAs($docente)->post('/incidentes', [
            'incident_category_id' => $this->category('VIOL_FISICA')->id,
            'course_id' => $this->course()->id,
            'occurred_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'location' => 'Patio',
            'description' => 'Empujones reiterados durante el recreo a un estudiante.',
            'participants' => [
                ['student_id' => $student->id, 'role' => 'afectado'],
                ['non_student_description' => 'Persona adulta externa', 'role' => 'testigo'],
                ['student_id' => '', 'non_student_description' => '', 'notes' => ''], // fila vacía: se descarta
            ],
            'attachments' => [$this->fakePdf(), UploadedFile::fake()->image('foto.png')],
        ]);

        $incident = Incident::firstOrFail();
        $response->assertRedirect(route('reportes.show', $incident));

        $this->assertMatchesRegularExpression('/^UECRC-\d{4}-\d{6}$/', $incident->code);
        $this->assertSame('registrado', $incident->status->value);
        $this->assertSame(CasePriority::Alta, $incident->priority); // la categoría sugiere prioridad alta
        $this->assertSame($docente->id, $incident->reporter_user_id);
        $this->assertSame($this->period()->id, $incident->academic_period_id);
        $this->assertCount(2, $incident->participants);
        $this->assertCount(2, $incident->attachments);
        $this->assertDatabaseHas('case_status_history', ['incident_id' => $incident->id, 'to_status' => 'registrado']);
        $this->assertDatabaseHas('case_events', ['incident_id' => $incident->id, 'event_type' => 'creado']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'incidente_registrado', 'auditable_id' => $incident->id]);
        Notification::assertSentTo($dece, IncidentReportedNotification::class);
    }

    public function test_valida_campos_obligatorios_e_involucrados(): void
    {
        $docente = $this->user(RoleSlug::Docente);

        $this->actingAs($docente)->post('/incidentes', [
            'description' => 'corta',
            'participants' => [['notes' => 'sin persona', 'role' => 'afectado']],
        ])->assertSessionHasErrors(['incident_category_id', 'description', 'participants.0.student_id']);

        $this->assertSame(0, Incident::count());
    }

    public function test_los_adjuntos_se_guardan_en_disco_privado_y_validan_el_contenido(): void
    {
        $docente = $this->user(RoleSlug::Docente);
        $base = ['incident_category_id' => $this->category()->id, 'description' => 'Descripción ficticia suficiente para la prueba.'];

        // Un ejecutable renombrado como .pdf se rechaza por su contenido real
        $fake = UploadedFile::fake()->createWithContent('virus.pdf', "MZ\x90\x00\x03binario-ejecutable");
        $this->actingAs($docente)->post('/incidentes', $base + ['attachments' => [$fake]])->assertSessionHasErrors();
        $this->assertSame(0, Incident::count());

        // Extensión no permitida
        $this->actingAs($docente)->post('/incidentes', $base + ['attachments' => [UploadedFile::fake()->create('script.php', 1)]])
            ->assertSessionHasErrors('attachments.0');

        $this->actingAs($docente)->post('/incidentes', $base + ['attachments' => [$this->fakePdf('mi evidencia.pdf')]])->assertRedirect();
        $attachment = Attachment::firstOrFail();

        $this->assertStringStartsWith('evidencias/', $attachment->stored_path);
        $this->assertStringNotContainsString('mi evidencia', $attachment->stored_path);
        Storage::disk('local')->assertExists($attachment->stored_path);
        $this->assertFileDoesNotExist(public_path($attachment->stored_path));
        $this->assertSame(64, strlen($attachment->sha256));
    }

    public function test_los_adjuntos_solo_se_descargan_con_autorizacion(): void
    {
        $docente = $this->user(RoleSlug::Docente);
        $this->actingAs($docente)->post('/incidentes', [
            'incident_category_id' => $this->category()->id,
            'description' => 'Descripción ficticia suficiente para la prueba.',
            'attachments' => [$this->fakePdf()],
        ]);
        $attachment = Attachment::firstOrFail();
        $url = route('attachments.download', $attachment);

        $this->get($url)->assertOk()->assertHeader('content-disposition'); // quien lo subió
        $this->actingAs($this->user(RoleSlug::Dece))->get($url)->assertOk();
        $this->actingAs($this->user(RoleSlug::Docente))->get($url)->assertForbidden();
        $this->actingAs($this->user(RoleSlug::Administrador))->get($url)->assertForbidden();
        $this->actingAs($this->user(RoleSlug::Policia))->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'evidencia_descargada']);
    }

    public function test_quien_reporta_ve_su_reporte_pero_no_el_expediente_ni_reportes_ajenos(): void
    {
        $docente = $this->user(RoleSlug::Docente);
        $mine = $this->incident($docente);
        $other = $this->incident($this->user(RoleSlug::Docente));

        $this->actingAs($docente);
        $this->get('/mis-reportes')->assertOk()->assertSee($mine->code)->assertDontSee($other->code);
        $this->get(route('reportes.show', $mine))->assertOk()->assertSee('Estado de atención');
        $this->get(route('reportes.show', $other))->assertForbidden();   // manipulación de URL
        $this->get(route('casos.show', $mine))->assertForbidden();       // expediente completo
        $this->get('/casos')->assertForbidden();
    }

    public function test_roles_sin_permiso_no_pueden_registrar(): void
    {
        foreach ([RoleSlug::Administrador, RoleSlug::Policia] as $role) {
            $this->actingAs($this->user($role))->get('/incidentes/registrar')->assertForbidden();
        }
    }
}
