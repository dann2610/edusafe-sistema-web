<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleSlug;
use App\Services\ReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCases;
use Tests\TestCase;

class DashboardReportsTest extends TestCase
{
    use CreatesCases, RefreshDatabase;

    protected bool $seed = true;

    public function test_el_dashboard_usa_datos_reales_y_no_muestra_datos_personales(): void
    {
        $student = $this->enrolledStudent();
        $this->incident(null, ['incident_category_id' => $this->category('CIBERACOSO')->id], [['student_id' => $student->id, 'role' => 'afectado']]);
        $this->incident(null, ['incident_category_id' => $this->category('CIBERACOSO')->id]);
        $this->incident(null, ['incident_category_id' => $this->category('ACOSO')->id, 'immediate_risk' => true]);

        $response = $this->actingAs($this->user(RoleSlug::Autoridad))->get('/indicadores')->assertOk();
        $response->assertSee('Ciberacoso')->assertSee('dashboard-data', false);
        $response->assertDontSee($student->first_names)->assertDontSee('Descripción ficticia');

        $charts = $response->viewData('charts');
        $this->assertSame(2, $charts['categorias']['Ciberacoso']);
        $this->assertSame(1, $charts['categorias']['Acoso escolar']);
        $this->assertSame(3, $charts['estados']['Registrado']);
        $this->assertSame(1, $charts['prioridad']['Urgente']);
        $this->assertSame(3, (int) $response->viewData('summary')['total']);
    }

    public function test_el_dashboard_esta_restringido(): void
    {
        foreach ([RoleSlug::Docente, RoleSlug::Administrador, RoleSlug::Policia] as $role) {
            $this->actingAs($this->user($role))->get('/indicadores')->assertForbidden();
        }
        $this->actingAs($this->user(RoleSlug::Dece))->get('/indicadores')->assertOk();
    }

    public function test_reportes_pdf_y_excel_agregados(): void
    {
        $this->incident();
        $this->actingAs($this->user(RoleSlug::Autoridad));

        $this->get('/reportes')->assertOk()->assertSee('Descargar PDF')->assertDontSee('Listado de casos (confidencial)');

        $pdf = $this->get('/reportes/pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith("%PDF", $pdf->getContent());

        $xlsx = $this->get('/reportes/excel');
        $xlsx->assertOk();
        $this->assertStringContainsString('.xlsx', $xlsx->headers->get('content-disposition'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'reporte_exportado']);
    }

    public function test_el_listado_identificable_solo_para_dece_y_queda_auditado(): void
    {
        $this->incident();
        $this->actingAs($this->user(RoleSlug::Autoridad))->get('/reportes/casos-excel')->assertForbidden();

        $dece = $this->user(RoleSlug::Dece);
        $this->actingAs($dece)->get('/reportes')->assertSee('Listado de casos (confidencial)');
        $this->get('/reportes/casos-excel')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'reporte_exportado', 'user_id' => $dece->id]);
    }

    public function test_los_textos_exportados_no_se_interpretan_como_formulas(): void
    {
        $exporter = app(ReportExporter::class);
        $this->assertSame("'=HYPERLINK(\"x\")", $exporter->safe('=HYPERLINK("x")'));
        $this->assertSame("'+cmd", $exporter->safe('+cmd'));
        $this->assertSame("'@SUM(A1)", $exporter->safe('@SUM(A1)'));
        $this->assertSame('Acoso escolar', $exporter->safe('Acoso escolar'));
    }

    public function test_la_bitacora_es_de_solo_lectura_y_restringida(): void
    {
        $this->actingAs($this->user(RoleSlug::Administrador))->get('/bitacora')->assertOk()->assertSee('Bitácora de auditoría');
        $this->actingAs($this->user(RoleSlug::Docente))->get('/bitacora')->assertForbidden();
        $this->actingAs($this->user(RoleSlug::Dece))->get('/bitacora')->assertForbidden();
    }
}
