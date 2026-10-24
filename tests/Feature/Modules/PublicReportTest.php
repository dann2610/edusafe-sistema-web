<?php

namespace Tests\Feature\Modules;

use App\Enums\CasePriority;
use App\Enums\FieldType;
use App\Enums\RoleSlug;
use App\Http\Requests\PublicReportRequest;
use App\Models\Incident;
use App\Models\Institution;
use App\Models\Module;
use App\Notifications\IncidentReportedNotification;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCases;
use Tests\TestCase;

class PublicReportTest extends TestCase
{
    use CreatesCases, RefreshDatabase;

    protected bool $seed = true;

    private function payload(array $override = []): array
    {
        return $override + [
            'incident_category_id' => $this->category('CIBERACOSO')->id,
            'occurred_date' => now()->subDays(2)->toDateString(),
            'occurred_time' => '10:30',
            'location' => 'Redes sociales',
            'description' => 'Están compartiendo fotos de un compañero para burlarse en un grupo.',
            'is_ongoing' => 'si',
            'authority_informed' => 'no',
            'reporter_relationship' => 'estudiante',
            'reporter_mode' => 'con_contacto',
            'reporter_contact_name' => 'Persona ficticia',
            'reporter_contact' => 'correo@ejemplo.test',
            'privacy' => '1',
            'confirm' => '1',
            'form_token' => Crypt::encryptString((string) (time() - 30)),
        ];
    }

    private function setModule(string $key, bool $enabled, ?Institution $institution = null): void
    {
        $institution ??= Institution::default();
        $module = Module::where('key', $key)->firstOrFail();
        if ($enabled) {
            app(ModuleService::class)->setAuthorized($institution, $module, true, $this->user(RoleSlug::Superadmin));
        }
        app(ModuleService::class)->setEnabled($institution, $module, $enabled, $this->user(RoleSlug::Superadmin));
        app(ModuleService::class)->flush();
    }

    public function test_la_pagina_publica_muestra_lo_necesario_sin_iniciar_sesion(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Unidad Educativa Cardenal Richard Cushing')
            ->assertSee('Reportar un incidente')
            ->assertSee('Cómo funciona')
            ->assertSee('Si hay peligro inmediato')
            ->assertSee('ECU 911')
            ->assertSee('no es anónimo por completo', false)
            ->assertSee('name="viewport"', false)
            ->assertSee(route('public.report'))
            ->assertSee(route('login'));
        $this->get('/privacidad')->assertOk()->assertSee('Finalidad');
    }

    public function test_el_formulario_tiene_los_pasos_y_las_categorias_activas(): void
    {
        $this->get('/reportar')->assertOk()
            ->assertSee('data-step="incidente"', false)
            ->assertSee('data-step="personas"', false)
            ->assertSee('data-step="reporta"', false)
            ->assertSee('data-step="revision"', false)
            ->assertDontSee('data-step="evidencias"', false)
            ->assertSee('Ciberacoso')
            ->assertSee('name="form_token"', false)
            ->assertDontSee('type="file"', false);
    }

    public function test_un_reporte_publico_valido_crea_el_caso_y_notifica_al_dece(): void
    {
        Notification::fake();
        $dece = $this->user(RoleSlug::Dece);

        $this->post('/reportar', $this->payload([
            'immediate_risk' => '1',
            'persons' => [['role' => 'afectado', 'description' => 'estudiante de 9.º B'], ['role' => 'testigo', 'description' => '']],
        ]))->assertRedirect(route('public.report.sent'));

        $incident = Incident::latest('id')->firstOrFail();
        $this->assertSame('publico', $incident->source->value);
        $this->assertNull($incident->reporter_user_id);
        $this->assertSame(CasePriority::Urgente, $incident->priority);
        $this->assertTrue($incident->privacy_notice_accepted);
        $this->assertSame('si', $incident->is_ongoing);
        $this->assertSame('no', $incident->authority_informed);
        $this->assertSame('con_contacto', $incident->reporter_mode);
        $this->assertTrue($incident->occurred_time_known);
        $this->assertSame(1, $incident->participants()->count(), 'Las filas sin descripción se descartan');
        $this->assertNotNull($incident->public_access_hash, 'Con consulta de estado activa se genera una clave');
        $this->assertSame(1, $incident->statusHistory()->count());
        Notification::assertSentTo($dece, IncidentReportedNotification::class);

        $this->get(route('public.report.sent'))->assertOk()
            ->assertSee($incident->code)->assertSee('Clave de consulta')->assertSee('ECU 911')
            ->assertSee('no significa que los hechos estén verificados', false);
    }

    public function test_acepta_fecha_desconocida(): void
    {
        $this->post('/reportar', $this->payload(['occurred_date' => null, 'occurred_time' => null, 'date_unknown' => '1']))->assertRedirect(route('public.report.sent'));
        $incident = Incident::latest('id')->firstOrFail();
        $this->assertNull($incident->occurred_at);
        $this->assertFalse($incident->occurred_time_known);
    }

    public function test_exige_aceptar_privacidad_confirmar_y_campos_obligatorios(): void
    {
        $this->post('/reportar', $this->payload(['privacy' => null, 'confirm' => null, 'description' => 'corto', 'is_ongoing' => 'quizas', 'occurred_date' => null]))
            ->assertSessionHasErrors(['privacy', 'confirm', 'description', 'is_ongoing', 'occurred_date']);
        $this->post('/reportar', $this->payload(['occurred_date' => now()->addDay()->toDateString()]))->assertSessionHasErrors('occurred_date');
        $this->assertSame(0, Incident::count());
    }

    public function test_reporte_sin_contacto_solo_si_la_institucion_lo_permite(): void
    {
        $sinContacto = ['reporter_mode' => 'sin_contacto', 'reporter_contact_name' => null, 'reporter_contact' => null];

        Institution::default()->forceFill(['allow_reports_without_contact' => false])->save();
        $this->post('/reportar', $this->payload($sinContacto))->assertSessionHasErrors('reporter_mode');
        $this->post('/reportar', $this->payload(['reporter_contact' => null]))->assertSessionHasErrors('reporter_contact');
        $this->assertSame(0, Incident::count());

        Institution::default()->forceFill(['allow_reports_without_contact' => true])->save();
        $this->post('/reportar', $this->payload($sinContacto + ['reporter_contact' => 'se descarta@ejemplo.test']))->assertRedirect();
        $incident = Incident::latest('id')->firstOrFail();
        $this->assertSame('sin_contacto', $incident->reporter_mode);
        $this->assertNull($incident->reporter_contact, 'Sin contacto no se guarda ningún dato de contacto');
    }

    public function test_bloquea_robots_por_campo_trampa_y_envio_inmediato(): void
    {
        $this->post('/reportar', $this->payload(['sitio_web' => 'http://spam.test']))->assertSessionHasErrors('sitio_web');
        $this->post('/reportar', $this->payload(['form_token' => PublicReportRequest::token()]))->assertSessionHasErrors('form_token');
        $this->post('/reportar', $this->payload(['form_token' => 'manipulado']))->assertSessionHasErrors('form_token');
        $this->assertSame(0, Incident::count());
    }

    public function test_limita_la_cantidad_de_reportes_segun_la_institucion(): void
    {
        Institution::default()->forceFill(['public_report_hourly_limit' => 2])->save();
        $this->post('/reportar', $this->payload())->assertRedirect();
        $this->post('/reportar', $this->payload())->assertRedirect();
        $this->post('/reportar', $this->payload())->assertStatus(429);
        $this->assertSame(2, Incident::count());
    }

    public function test_si_se_desactiva_el_modulo_el_canal_publico_no_existe(): void
    {
        $this->setModule('consulta_estado', false); // primero el módulo que depende de él
        $this->setModule('reporte_publico', false);
        $this->get('/reportar')->assertNotFound();
        $this->post('/reportar', $this->payload())->assertNotFound();
        $this->get('/consultar')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee(route('public.report'))->assertSee('no está habilitado');
        $this->assertSame(0, Incident::count());
    }

    public function test_solo_acepta_categorias_habilitadas_para_el_publico(): void
    {
        $cat = $this->category('OTRA');
        $cat->update(['available_in_public_form' => false]);
        $this->post('/reportar', $this->payload(['incident_category_id' => $cat->id]))->assertSessionHasErrors('incident_category_id');
        $this->get('/reportar')->assertDontSee('value="'.$cat->id.'"', false);
    }

    public function test_evidencias_solo_con_el_modulo_activo(): void
    {
        Storage::fake('local');
        $file = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('captura.pdf', "%PDF-1.4\n%%EOF\n");

        $this->post('/reportar', $this->payload(['attachments' => [$file()]]))->assertSessionHasErrors('attachments');
        $this->assertSame(0, Incident::count());

        $this->setModule('evidencias_publicas', true);
        $this->get('/reportar')->assertSee('data-step="evidencias"', false)->assertSee('type="file"', false);
        $this->post('/reportar', $this->payload(['attachments' => [$file()]]))->assertRedirect(route('public.report.sent'));
        $this->assertSame(1, Incident::latest('id')->first()->attachments()->count());

        $this->post('/reportar', $this->payload(['attachments' => [
            \Illuminate\Http\UploadedFile::fake()->create('programa.exe', 10, 'application/octet-stream'),
        ]]))->assertSessionHasErrors('attachments.0');
    }

    public function test_campos_adicionales_de_la_categoria_se_validan_y_se_guardan(): void
    {
        $cat = $this->category('CIBERACOSO');
        $cat->fields()->create(['key' => 'plataforma', 'label' => 'Plataforma', 'type' => FieldType::Lista, 'options' => ['WhatsApp', 'Instagram', 'Otra'],
            'is_required' => true, 'show_in_public_form' => true, 'sort_order' => 1, 'is_active' => true]);

        $this->get('/reportar')->assertSee('Plataforma')->assertSee('WhatsApp');
        $this->post('/reportar', $this->payload())->assertSessionHasErrors('extra.plataforma');
        $this->post('/reportar', $this->payload(['extra' => ['plataforma' => '<script>']]))->assertSessionHasErrors('extra.plataforma');
        $this->post('/reportar', $this->payload(['extra' => ['plataforma' => 'Instagram']]))->assertRedirect(route('public.report.sent'));

        $data = Incident::latest('id')->first()->extra_data;
        $this->assertSame('plataforma', $data[0]['key']);
        $this->assertSame('Instagram', $data[0]['value']);
        $this->assertSame('Plataforma', $data[0]['label'], 'Se guarda una copia de la etiqueta para que los reportes antiguos no se rompan');
    }

    public function test_consulta_de_estado_con_codigo_y_clave(): void
    {
        $this->post('/reportar', $this->payload());
        $report = session('report');
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $report['key']);

        $this->post('/consultar', ['code' => $report['code'], 'key' => $report['key']])->assertOk()
            ->assertSee('Registrado')->assertDontSee('Están compartiendo fotos');
        $this->post('/consultar', ['code' => $report['code'], 'key' => 'AAAA-BBBB'])->assertOk()->assertSee('No encontramos un reporte');
        $this->post('/consultar', ['code' => 'NO-EXISTE', 'key' => $report['key']])->assertOk()->assertSee('No encontramos un reporte');
    }

    public function test_la_consulta_de_estado_limita_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/consultar', ['code' => 'X-'.$i, 'key' => 'AAAA-BBBB'])->assertOk();
        }
        $this->post('/consultar', ['code' => 'X-9', 'key' => 'AAAA-BBBB'])->assertStatus(429);
    }

    public function test_una_institucion_no_activa_no_tiene_pagina_publica(): void
    {
        $other = Institution::where('slug', 'uedemo')->first() ?? Institution::forceCreate([
            'name' => 'Institución ficticia', 'slug' => 'ficticia', 'code_prefix' => 'FIC', 'status' => 'activa', 'is_default' => false,
        ]);
        app(ModuleService::class)->provision($other);
        $this->get('/i/'.$other->slug)->assertOk()->assertSee($other->name);
        $other->forceFill(['status' => 'suspendida'])->save();
        $this->get('/i/'.$other->slug)->assertNotFound();
        $this->get('/i/'.$other->slug.'/reportar')->assertNotFound();
    }
}
