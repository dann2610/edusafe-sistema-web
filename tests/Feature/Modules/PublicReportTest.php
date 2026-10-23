<?php

namespace Tests\Feature\Modules;

use App\Enums\CasePriority;
use App\Enums\RoleSlug;
use App\Http\Requests\PublicReportRequest;
use App\Models\Incident;
use App\Notifications\IncidentReportedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
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
            'description' => 'Están compartiendo fotos de un compañero para burlarse en un grupo.',
            'reporter_relationship' => 'estudiante',
            'privacy' => '1',
            'form_token' => Crypt::encryptString((string) (time() - 30)),
        ];
    }

    public function test_la_pagina_publica_muestra_lo_necesario_sin_iniciar_sesion(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Unidad Educativa Cardenal Richard Cushing')
            ->assertSee('Reportar un incidente')
            ->assertSee('Iniciar sesión')
            ->assertSee('ECU 911')
            ->assertSee('Aviso de privacidad')
            ->assertSee('name="viewport"', false)
            ->assertSee(route('public.report'));
        $this->get('/privacidad')->assertOk()->assertSee('Finalidad');
    }

    public function test_el_boton_reportar_abre_un_formulario_funcional(): void
    {
        $this->get('/reportar')->assertOk()
            ->assertSee('Qué datos pedimos y para qué')
            ->assertSee('no es anónimo')
            ->assertSee('name="form_token"', false)
            ->assertDontSee('type="file"', false);
    }

    public function test_un_reporte_publico_valido_crea_el_caso_y_notifica_al_dece(): void
    {
        Notification::fake();
        $dece = $this->user(RoleSlug::Dece);

        $this->post('/reportar', $this->payload(['immediate_risk' => '1', 'reporter_contact' => 'correo@ejemplo.test']))
            ->assertRedirect(route('public.report.sent'));

        $incident = Incident::latest('id')->firstOrFail();
        $this->assertSame('publico', $incident->source->value);
        $this->assertNull($incident->reporter_user_id);
        $this->assertSame(CasePriority::Urgente, $incident->priority);
        $this->assertTrue($incident->privacy_notice_accepted);
        Notification::assertSentTo($dece, IncidentReportedNotification::class);

        $this->get(route('public.report.sent'))->assertOk()->assertSee($incident->code)->assertSee('ECU 911');
    }

    public function test_exige_aceptar_privacidad_y_campos_obligatorios(): void
    {
        $this->post('/reportar', $this->payload(['privacy' => null, 'description' => 'corto']))
            ->assertSessionHasErrors(['privacy', 'description']);
        $this->assertSame(0, Incident::count());
    }

    public function test_bloquea_robots_por_campo_trampa_y_envio_inmediato(): void
    {
        $this->post('/reportar', $this->payload(['sitio_web' => 'http://spam.test']))->assertSessionHasErrors('sitio_web');
        $this->post('/reportar', $this->payload(['form_token' => PublicReportRequest::token()]))->assertSessionHasErrors('form_token');
        $this->post('/reportar', $this->payload(['form_token' => 'manipulado']))->assertSessionHasErrors('form_token');
        $this->assertSame(0, Incident::count());
    }

    public function test_limita_la_cantidad_de_reportes_por_ip(): void
    {
        config(['seguridad_escolar.reportes_publicos.limite_por_hora' => 2]);
        $this->post('/reportar', $this->payload())->assertRedirect();
        $this->post('/reportar', $this->payload())->assertRedirect();
        $this->post('/reportar', $this->payload())->assertStatus(429);
        $this->assertSame(2, Incident::count());
    }

    public function test_si_se_deshabilita_el_canal_publico_no_acepta_reportes(): void
    {
        config(['seguridad_escolar.reportes_publicos.habilitados' => false]);
        $this->get('/reportar')->assertNotFound();
        $this->post('/reportar', $this->payload())->assertForbidden();
        $this->get('/')->assertOk()->assertDontSee(route('public.report'));
    }

    public function test_solo_acepta_categorias_habilitadas_para_el_publico(): void
    {
        $cat = $this->category('OTRA');
        $cat->update(['available_in_public_form' => false]);
        $this->post('/reportar', $this->payload(['incident_category_id' => $cat->id]))->assertSessionHasErrors('incident_category_id');
    }
}
