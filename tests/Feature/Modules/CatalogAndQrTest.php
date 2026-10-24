<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\IncidentCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesCases;
use Tests\TestCase;

class CatalogAndQrTest extends TestCase
{
    use CreatesCases, RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->user(RoleSlug::Administrador);
    }

    public function test_el_administrador_gestiona_categorias_sin_eliminarlas(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/catalogos')->assertOk()->assertSee('Categorías de incidentes');
        $this->get('/admin/categorias')->assertOk()->assertSee('Ciberacoso')->assertSee('Global');

        $this->post('/admin/categorias', ['code' => 'gr@oming', 'name' => 'Captación en línea', 'default_priority' => 'alta'])
            ->assertSessionHasErrors('code'); // formato de código
        $this->post('/admin/categorias', ['code' => 'CIBERACOSO', 'name' => 'Otra', 'default_priority' => 'alta'])
            ->assertSessionHasErrors('code'); // repite una global
        $this->post('/admin/categorias', ['code' => 'GROOMING', 'name' => 'Captación en línea', 'default_priority' => 'alta', 'sort_order' => 5])
            ->assertRedirect();

        $category = IncidentCategory::where('code', 'GROOMING')->firstOrFail();
        $this->assertSame($this->admin->institution_id, $category->institution_id, 'Las categorías del administrador son locales');
        $this->assertSame('alta', $category->default_priority->value);
        $this->assertFalse($category->available_in_public_form); // casilla no marcada

        $this->put("/admin/categorias/{$category->id}", ['code' => 'GROOMING', 'name' => 'Captación de menores en línea', 'default_priority' => 'urgente'])->assertRedirect();
        $this->get("/admin/categorias/{$category->id}")->assertOk()->assertSee('Historial de cambios')->assertSee('Captación de menores en línea');
        $log = \App\Models\AuditLog::where('action', 'categoria_actualizada')->latest('id')->firstOrFail();
        $this->assertSame('alta', $log->metadata['cambios']['prioridad']['antes']);

        $this->patch("/admin/categorias/{$category->id}/estado")->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'categoria_desactivada']);

        // Las globales son de solo lectura para la institución
        $global = $this->category('ACOSO');
        $this->put("/admin/categorias/{$global->id}", ['name' => 'Cambiado', 'default_priority' => 'normal'])->assertForbidden();
        $this->patch("/admin/categorias/{$global->id}/estado")->assertForbidden();
        $this->assertTrue($global->fresh()->is_active);
    }

    public function test_campos_adicionales_tipados_y_sin_codigo(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/categorias', ['code' => 'LOCAL1', 'name' => 'Categoría local', 'default_priority' => 'normal', 'available_in_public_form' => '1']);
        $category = IncidentCategory::where('code', 'LOCAL1')->firstOrFail();

        $this->post("/admin/categorias/{$category->id}/campos", ['key' => 'x', 'label' => 'Algo', 'type' => 'script'])->assertSessionHasErrors(['key', 'type']);
        $this->post("/admin/categorias/{$category->id}/campos", ['key' => 'lugar', 'label' => 'Lugar', 'type' => 'lista', 'options_text' => 'Solo una'])->assertSessionHasErrors('options_text');
        $this->post("/admin/categorias/{$category->id}/campos", ['key' => 'lugar', 'label' => 'Lugar', 'type' => 'lista', 'options_text' => "Aula\nPatio", 'show_in_public_form' => '1'])->assertRedirect();
        $field = $category->fields()->firstOrFail();
        $this->assertSame(['Aula', 'Patio'], $field->options);

        $this->put("/admin/categorias/{$category->id}/campos/{$field->id}", ['label' => 'Lugar', 'type' => 'texto'])->assertSessionHasErrors('type');
        $this->patch("/admin/categorias/{$category->id}/campos/{$field->id}/estado")->assertRedirect();
        $this->assertFalse($field->fresh()->is_active);
    }

    public function test_los_catalogos_estan_restringidos_al_administrador(): void
    {
        foreach ([RoleSlug::Dece, RoleSlug::Docente, RoleSlug::Autoridad] as $role) {
            $this->actingAs($this->user($role))->get('/admin/categorias')->assertForbidden();
            $this->get('/admin/catalogos/entidades')->assertForbidden();
            $this->get('/admin/estudiantes')->assertForbidden();
            $this->get('/plataforma/catalogos')->assertForbidden();
        }
        $this->actingAs($this->admin)->get('/admin/catalogos/inexistente')->assertNotFound();
        $this->get('/plataforma/instituciones')->assertForbidden();
    }

    public function test_periodo_vigente_unico_y_cursos_sin_duplicados(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/periodos', ['name' => '2026-2027', 'starts_on' => '2026-05-01', 'ends_on' => '2027-02-28'])->assertRedirect();
        $this->post('/admin/periodos', ['name' => '2027-2028', 'starts_on' => '2027-05-01', 'ends_on' => '2028-02-28'])->assertRedirect();
        $first = AcademicPeriod::where('name', '2026-2027')->first();
        $second = AcademicPeriod::where('name', '2027-2028')->first();
        $this->assertTrue($first->is_current);

        $this->patch("/admin/periodos/{$second->id}/vigente")->assertRedirect();
        $this->assertTrue($second->fresh()->is_current);
        $this->assertFalse($first->fresh()->is_current);
        $this->patch("/admin/periodos/{$second->id}/estado")->assertSessionHas('error');

        $course = ['academic_period_id' => $second->id, 'level' => '9.º EGB', 'parallel' => 'b', 'shift' => 'matutina'];
        $this->post('/admin/cursos', $course)->assertRedirect();
        $this->post('/admin/cursos', $course)->assertSessionHasErrors('level');
    }

    public function test_registro_e_importacion_atomica_de_estudiantes(): void
    {
        $course = $this->course();
        $this->actingAs($this->admin);

        $this->post('/admin/estudiantes', ['institutional_code' => 'E-1', 'first_names' => 'Ana', 'last_names' => 'Prueba', 'course_id' => $course->id])->assertRedirect();
        $this->assertSame($course->id, Student::where('institutional_code', 'E-1')->first()->enrollments()->value('course_id'));

        $bad = UploadedFile::fake()->createWithContent('e.csv', "codigo;nombres;apellidos;curso_id\nE-2;Luis;Ficticio;{$course->id}\nE-3;;SinNombre;\n");
        $this->post('/admin/estudiantes/importar', ['archivo' => $bad])->assertSessionHasErrors('archivo');
        $this->assertDatabaseMissing('students', ['institutional_code' => 'E-2']); // nada se guarda si hay errores

        $good = UploadedFile::fake()->createWithContent('e.csv', "codigo;nombres;apellidos;curso_id\nE-2;Luis;Ficticio;{$course->id}\nE-1;Ana María;Prueba;\n");
        $this->post('/admin/estudiantes/importar', ['archivo' => $good])->assertRedirect();
        $this->assertDatabaseHas('students', ['institutional_code' => 'E-2']);
        $this->assertSame('Ana María', Student::where('institutional_code', 'E-1')->value('first_names'));
    }

    public function test_qr_sin_url_configurada_no_se_genera(): void
    {
        config(['seguridad_escolar.qr.url_publica' => '']);
        $this->actingAs($this->admin)->get('/admin/qr')->assertOk()->assertSee('El QR aún no puede generarse');
        $this->get('/admin/qr/descargar/png')->assertNotFound();
    }

    public function test_qr_apunta_a_la_url_publica_configurada(): void
    {
        config(['seguridad_escolar.qr.url_publica' => 'https://seguridad.uecrc.edu.ec', 'seguridad_escolar.qr.destino' => 'formulario']);
        $this->actingAs($this->admin)->get('/admin/qr')->assertOk()
            ->assertSee('https://seguridad.uecrc.edu.ec/i/'.$this->admin->institution->slug.'/reportar')->assertSee('<svg', false)->assertDontSee('no usa HTTPS');

        $png = $this->get('/admin/qr/descargar/png')->assertOk()->assertHeader('content-type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $png->getContent());
        $this->get('/admin/qr/descargar/svg')->assertOk()->assertHeader('content-type', 'image/svg+xml');

        config(['seguridad_escolar.qr.url_publica' => 'http://localhost:8000']);
        $this->get('/admin/qr')->assertSee('no usa HTTPS')->assertSee('es local');

        $this->actingAs($this->user(RoleSlug::Dece))->get('/admin/qr')->assertForbidden();
    }
}
