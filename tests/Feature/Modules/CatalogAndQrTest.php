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

        $this->post('/admin/catalogos/categorias', ['code' => 'grooming', 'name' => 'Captación en línea'])
            ->assertSessionHasErrors('code'); // formato de código
        $this->post('/admin/catalogos/categorias', ['code' => 'GROOMING', 'name' => 'Captación en línea', 'suggests_high_priority' => '1', 'sort_order' => 5])
            ->assertRedirect('/admin/catalogos/categorias');

        $category = IncidentCategory::where('code', 'GROOMING')->firstOrFail();
        $this->assertTrue($category->suggests_high_priority);
        $this->assertFalse($category->available_in_public_form); // casilla no marcada

        $this->patch("/admin/catalogos/categorias/{$category->id}/estado")->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalogo_desactivado']);

        $this->post('/admin/catalogos/categorias', ['code' => 'OTRO2', 'name' => 'Captación en línea'])->assertSessionHasErrors('name');
    }

    public function test_los_catalogos_estan_restringidos_al_administrador(): void
    {
        foreach ([RoleSlug::Dece, RoleSlug::Docente, RoleSlug::Autoridad] as $role) {
            $this->actingAs($this->user($role))->get('/admin/catalogos/categorias')->assertForbidden();
            $this->get('/admin/estudiantes')->assertForbidden();
        }
        $this->actingAs($this->admin)->get('/admin/catalogos/inexistente')->assertNotFound();
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
            ->assertSee('https://seguridad.uecrc.edu.ec/reportar')->assertSee('<svg', false)->assertDontSee('no usa HTTPS');

        $png = $this->get('/admin/qr/descargar/png')->assertOk()->assertHeader('content-type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $png->getContent());
        $this->get('/admin/qr/descargar/svg')->assertOk()->assertHeader('content-type', 'image/svg+xml');

        config(['seguridad_escolar.qr.url_publica' => 'http://localhost:8000']);
        $this->get('/admin/qr')->assertSee('no usa HTTPS')->assertSee('es local');

        $this->actingAs($this->user(RoleSlug::Dece))->get('/admin/qr')->assertForbidden();
    }
}
