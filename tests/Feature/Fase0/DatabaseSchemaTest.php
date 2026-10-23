<?php

namespace Tests\Feature\Fase0;

use App\Enums\CaseEventType;
use App\Enums\CaseStatus;
use App\Enums\ComplianceStatus;
use App\Enums\ExternalEntityType;
use App\Enums\ParticipantRole;
use App\Enums\ReferralChannel;
use App\Enums\ReferralStatus;
use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\AuditLog;
use App\Models\CaseEvent;
use App\Models\CaseStatusHistory;
use App\Models\FollowUpType;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true; // roles, permisos y catálogos (sin datos demo)

    private function makeInternalIncident(User $reporter): Incident
    {
        $incident = new Incident([
            'incident_category_id' => IncidentCategory::first()->id,
            'description' => 'Descripción de prueba (dato ficticio).',
            'immediate_risk' => false,
        ]);
        $incident->code = Incident::generateCode();
        $incident->source = 'interno';
        $incident->reporter_user_id = $reporter->id;
        $incident->save();

        return $incident;
    }

    public function test_la_conexion_usa_postgresql(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
    }

    public function test_existen_todas_las_tablas_del_modelo(): void
    {
        foreach ([
            'users', 'roles', 'permissions', 'permission_role', 'sessions', 'password_reset_tokens',
            'academic_periods', 'courses', 'students', 'enrollments',
            'incident_categories', 'measure_types', 'follow_up_types', 'external_entities',
            'incidents', 'incident_participants', 'case_assignments', 'case_status_history', 'case_events',
            'follow_ups', 'protective_measures', 'referrals', 'attachments', 'audit_logs', 'notifications',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Falta la tabla {$table}");
        }
    }

    public function test_los_seeders_son_idempotentes(): void
    {
        $counts = fn () => [Db::table('roles')->count(), DB::table('permissions')->count(), DB::table('permission_role')->count(),
            DB::table('incident_categories')->count(), DB::table('measure_types')->count(), DB::table('external_entities')->count()];
        $before = $counts();
        $this->seed([RolesAndPermissionsSeeder::class, CatalogSeeder::class]);
        $this->assertSame($before, $counts());
        $this->assertSame(count(RoleSlug::cases()), $before[0]);
    }

    public function test_el_seeder_de_produccion_no_crea_usuarios_ni_estudiantes(): void
    {
        $this->assertSame(0, User::count());
        $this->assertSame(0, DB::table('students')->count());
    }

    public function test_el_administrador_tecnico_no_tiene_acceso_a_expedientes(): void
    {
        $admin = User::factory()->role(RoleSlug::Administrador)->create();
        $this->assertTrue($admin->hasPermission('usuarios.gestionar'));
        foreach (['casos.ver_todos', 'casos.bandeja', 'casos.gestionar', 'reportes.exportar_identificable', 'derivaciones.ver_entidad'] as $p) {
            $this->assertFalse($admin->hasPermission($p), "El administrador no debe tener {$p}");
        }
    }

    public function test_la_policia_solo_puede_ver_derivaciones_de_su_entidad(): void
    {
        $police = User::factory()->role(RoleSlug::Policia)->create();
        $this->assertSame(['derivaciones.ver_entidad'], $police->role->permissions()->pluck('slug')->all());
    }

    public function test_un_usuario_inactivo_no_tiene_permisos(): void
    {
        $dece = User::factory()->role(RoleSlug::Dece)->inactive()->create();
        $this->assertFalse($dece->hasPermission('casos.bandeja'));
    }

    public function test_las_contrasenas_se_almacenan_con_hash(): void
    {
        $user = User::factory()->create(['password' => 'Clave.Plana-2026']);
        $stored = DB::table('users')->where('id', $user->id)->value('password');
        $this->assertNotSame('Clave.Plana-2026', $stored);
        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertTrue(Hash::check('Clave.Plana-2026', $stored));
    }

    public function test_el_correo_es_unico_sin_distinguir_mayusculas(): void
    {
        User::factory()->create(['email' => 'repetido@uecrc.test']);
        $this->assertDatabaseRejects(fn () => User::factory()->create(['email' => 'REPETIDO@uecrc.test']));
    }

    public function test_los_codigos_de_caso_son_unicos_y_con_formato(): void
    {
        $reporter = User::factory()->role(RoleSlug::Docente)->create();
        $a = $this->makeInternalIncident($reporter);
        $b = $this->makeInternalIncident($reporter);

        $this->assertNotSame($a->code, $b->code);
        $this->assertMatchesRegularExpression('/^UECRC-\d{4}-\d{6}$/', $a->code);
        $this->assertSame(CaseStatus::Registrado, $a->fresh()->status);

        $this->assertDatabaseRejects(fn () => DB::table('incidents')->where('id', $b->id)->update(['code' => $a->code]));
    }

    public function test_la_base_rechaza_estados_y_prioridades_invalidos(): void
    {
        $incident = $this->makeInternalIncident(User::factory()->role(RoleSlug::Docente)->create());
        $this->assertDatabaseRejects(fn () => DB::table('incidents')->where('id', $incident->id)->update(['status' => 'archivado']));
        $this->assertDatabaseRejects(fn () => DB::table('incidents')->where('id', $incident->id)->update(['priority' => 'maxima']));
        // Un caso no puede quedar "cerrado" sin fecha de cierre
        $this->assertDatabaseRejects(fn () => DB::table('incidents')->where('id', $incident->id)->update(['status' => 'cerrado']));
    }

    public function test_un_reporte_interno_exige_usuario_registrador(): void
    {
        $this->assertDatabaseRejects(fn () => DB::table('incidents')->insert([
            'code' => 'X-1', 'incident_category_id' => IncidentCategory::first()->id,
            'description' => 'x', 'source' => 'interno', 'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    public function test_un_reporte_publico_puede_registrarse_sin_usuario(): void
    {
        $incident = new Incident(['incident_category_id' => IncidentCategory::first()->id, 'description' => 'Reporte público ficticio.', 'privacy_notice_accepted' => true]);
        $incident->code = Incident::generateCode();
        $incident->source = 'publico';
        $incident->save();
        $this->assertNull($incident->fresh()->reporter_user_id);
    }

    public function test_las_restricciones_check_coinciden_con_los_enums(): void
    {
        $def = fn (string $name) => DB::selectOne('SELECT pg_get_constraintdef(oid) AS d FROM pg_constraint WHERE conname = ?', [$name])->d;

        $map = [
            'incidents_status_check' => CaseStatus::values(),
            'incident_participants_role_check' => ParticipantRole::values(),
            'protective_measures_compliance_check' => ComplianceStatus::values(),
            'referrals_channel_check' => ReferralChannel::values(),
            'referrals_status_check' => ReferralStatus::values(),
            'external_entities_type_check' => ExternalEntityType::values(),
            'case_events_type_check' => CaseEventType::values(),
        ];
        foreach ($map as $constraint => $values) {
            $d = $def($constraint);
            foreach ($values as $v) {
                $this->assertStringContainsString("'{$v}'", $d, "{$constraint} no admite '{$v}'");
            }
        }
    }

    public function test_la_bitacora_es_inmutable_en_la_aplicacion_y_en_la_base(): void
    {
        $log = AuditLog::create(['action' => 'prueba', 'result' => 'exito']);

        $this->assertDatabaseRejects(fn () => $log->update(['action' => 'alterada']), LogicException::class);
        $this->assertDatabaseRejects(fn () => $log->delete(), LogicException::class);
        $e = $this->assertDatabaseRejects(fn () => DB::table('audit_logs')->where('id', $log->id)->update(['action' => 'alterada']));
        $this->assertStringContainsString('Registro inmutable', $e->getMessage());
        $this->assertDatabaseRejects(fn () => DB::table('audit_logs')->where('id', $log->id)->delete());

        $this->assertSame('prueba', DB::table('audit_logs')->where('id', $log->id)->value('action'));
    }

    public function test_el_historial_del_caso_es_inmutable(): void
    {
        $user = User::factory()->role(RoleSlug::Docente)->create();
        $incident = $this->makeInternalIncident($user);
        $history = CaseStatusHistory::create(['incident_id' => $incident->id, 'to_status' => 'registrado', 'changed_by' => $user->id]);
        $event = CaseEvent::create(['incident_id' => $incident->id, 'event_type' => 'creado', 'user_id' => $user->id, 'summary' => 'Caso registrado']);

        $this->assertDatabaseRejects(fn () => DB::table('case_status_history')->where('id', $history->id)->update(['to_status' => 'cerrado']));
        $this->assertDatabaseRejects(fn () => DB::table('case_events')->where('id', $event->id)->delete());
        $this->assertDatabaseRejects(fn () => DB::statement('TRUNCATE case_events CASCADE'));
    }

    public function test_no_se_eliminan_catalogos_vinculados_a_casos(): void
    {
        $incident = $this->makeInternalIncident(User::factory()->role(RoleSlug::Docente)->create());
        $this->assertDatabaseRejects(fn () => DB::table('incident_categories')->where('id', $incident->incident_category_id)->delete());
        $this->assertDatabaseRejects(fn () => DB::table('incidents')->where('id', $incident->id)->delete());
    }

    public function test_un_responsable_no_puede_tener_dos_asignaciones_activas_en_el_mismo_caso(): void
    {
        $dece = User::factory()->role(RoleSlug::Dece)->create();
        $incident = $this->makeInternalIncident(User::factory()->role(RoleSlug::Docente)->create());
        $row = ['incident_id' => $incident->id, 'user_id' => $dece->id, 'assigned_by' => $dece->id, 'assignment_role' => 'principal', 'created_at' => now(), 'updated_at' => now()];

        DB::table('case_assignments')->insert($row);
        $this->assertDatabaseRejects(fn () => DB::table('case_assignments')->insert($row));

        // Tras liberar la asignación, puede volver a asignarse (queda historial)
        DB::table('case_assignments')->where('incident_id', $incident->id)->update(['unassigned_at' => now(), 'unassigned_by' => $dece->id]);
        DB::table('case_assignments')->insert($row);
        $this->assertSame(2, DB::table('case_assignments')->where('incident_id', $incident->id)->count());
    }

    public function test_una_correccion_de_seguimiento_exige_motivo(): void
    {
        $dece = User::factory()->role(RoleSlug::Dece)->create();
        $incident = $this->makeInternalIncident($dece);
        $base = ['incident_id' => $incident->id, 'follow_up_type_id' => FollowUpType::first()->id, 'user_id' => $dece->id,
            'performed_at' => now(), 'description' => 'Actuación ficticia', 'created_at' => now(), 'updated_at' => now()];
        $original = DB::table('follow_ups')->insertGetId($base);

        $this->assertDatabaseRejects(fn () => DB::table('follow_ups')->insert($base + ['corrects_follow_up_id' => $original]));
        DB::table('follow_ups')->insert($base + ['corrects_follow_up_id' => $original, 'correction_reason' => 'Fecha mal registrada']);
        $this->assertSame(2, DB::table('follow_ups')->where('incident_id', $incident->id)->count());

        // El original no puede editarse ni borrarse
        $this->assertDatabaseRejects(fn () => DB::table('follow_ups')->where('id', $original)->update(['description' => 'alterada']));
        $this->assertDatabaseRejects(fn () => DB::table('follow_ups')->where('id', $original)->delete());
    }

    public function test_solo_existe_un_periodo_lectivo_vigente(): void
    {
        $a = AcademicPeriod::create(['name' => 'P1', 'starts_on' => '2025-05-01', 'ends_on' => '2026-02-28']);
        $b = AcademicPeriod::create(['name' => 'P2', 'starts_on' => '2026-05-01', 'ends_on' => '2027-02-28']);
        $a->markAsCurrent();
        $b->markAsCurrent();

        $this->assertFalse($a->fresh()->is_current);
        $this->assertTrue($b->fresh()->is_current);
        $this->assertDatabaseRejects(fn () => DB::table('academic_periods')->where('id', $a->id)->update(['is_current' => true]));
        $this->assertDatabaseRejects(fn () => AcademicPeriod::create(['name' => 'P3', 'starts_on' => '2027-05-01', 'ends_on' => '2027-01-01']));
    }

    public function test_los_campos_criticos_del_caso_no_son_asignables_en_masa(): void
    {
        $guarded = ['status', 'priority', 'code', 'source', 'reporter_user_id', 'closed_at'];
        $this->assertEmpty(array_intersect($guarded, (new Incident)->getFillable()));
        $this->assertEmpty(array_intersect(['role_id', 'is_active', 'external_entity_id'], (new User)->getFillable()));
    }
}
