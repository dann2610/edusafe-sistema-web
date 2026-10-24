<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aislamiento de datos por institución.
 * - Agrega institution_id a las tablas de negocio.
 * - CONSERVA los datos existentes: los asigna a la institución por defecto.
 * - Los catálogos existentes quedan como GLOBALES (institution_id NULL).
 * - Las restricciones de unicidad pasan a ser por institución.
 * Las tablas hijas (participantes, seguimientos, medidas, derivaciones,
 * adjuntos, asignaciones) heredan la institución a través del caso.
 */
return new class extends Migration
{
    /** tabla => obligatorio */
    private array $tables = [
        'users' => false,             // el superadministrador no pertenece a una institución
        'academic_periods' => true,
        'courses' => true,
        'students' => true,
        'incidents' => true,
        'incident_categories' => false, // NULL = categoría global
        'external_entities' => false,
        'measure_types' => false,
        'follow_up_types' => false,
        'audit_logs' => false,
    ];

    public function up(): void
    {
        $default = DB::table('institutions')->where('is_default', true)->value('id');

        foreach (array_keys($this->tables) as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->restrictOnDelete();
                $t->index('institution_id');
            });
        }

        // Backfill: los datos de negocio existentes pertenecen a la institución inicial
        foreach (['users', 'academic_periods', 'courses', 'students', 'incidents'] as $table) {
            DB::table($table)->whereNull('institution_id')->update(['institution_id' => $default]);
        }
        // La bitácora es inmutable: se desactiva el trigger solo para este backfill
        DB::unprepared('ALTER TABLE audit_logs DISABLE TRIGGER audit_logs_immutable');
        DB::table('audit_logs')->whereNull('institution_id')->update(['institution_id' => $default]);
        DB::unprepared('ALTER TABLE audit_logs ENABLE TRIGGER audit_logs_immutable');

        foreach ($this->tables as $table => $required) {
            if ($required) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN institution_id SET NOT NULL");
            }
        }

        // Unicidad por institución
        DB::statement('ALTER TABLE academic_periods DROP CONSTRAINT IF EXISTS academic_periods_name_unique');
        DB::statement('CREATE UNIQUE INDEX academic_periods_institution_name_unique ON academic_periods (institution_id, name)');
        DB::statement('DROP INDEX IF EXISTS academic_periods_single_current');
        DB::statement('CREATE UNIQUE INDEX academic_periods_single_current ON academic_periods (institution_id) WHERE is_current = true');

        DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_institutional_code_unique');
        DB::statement('CREATE UNIQUE INDEX students_institution_code_unique ON students (institution_id, institutional_code)');

        foreach (['incident_categories' => ['code', 'name'], 'external_entities' => ['name'], 'measure_types' => ['name'], 'follow_up_types' => ['name']] as $table => $cols) {
            foreach ($cols as $col) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_{$col}_unique");
                DB::statement("CREATE UNIQUE INDEX {$table}_scope_{$col}_unique ON {$table} (COALESCE(institution_id, 0), {$col})");
            }
        }

        DB::statement('CREATE INDEX incidents_institution_status_index ON incidents (institution_id, status)');
        DB::statement('CREATE INDEX incidents_institution_created_index ON incidents (institution_id, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS incidents_institution_status_index');
        DB::statement('DROP INDEX IF EXISTS incidents_institution_created_index');
        foreach (['incident_categories' => ['code', 'name'], 'external_entities' => ['name'], 'measure_types' => ['name'], 'follow_up_types' => ['name']] as $table => $cols) {
            foreach ($cols as $col) {
                DB::statement("DROP INDEX IF EXISTS {$table}_scope_{$col}_unique");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_{$col}_unique UNIQUE ({$col})");
            }
        }
        DB::statement('DROP INDEX IF EXISTS students_institution_code_unique');
        DB::statement('ALTER TABLE students ADD CONSTRAINT students_institutional_code_unique UNIQUE (institutional_code)');
        DB::statement('DROP INDEX IF EXISTS academic_periods_single_current');
        DB::statement('CREATE UNIQUE INDEX academic_periods_single_current ON academic_periods (is_current) WHERE is_current = true');
        DB::statement('DROP INDEX IF EXISTS academic_periods_institution_name_unique');
        DB::statement('ALTER TABLE academic_periods ADD CONSTRAINT academic_periods_name_unique UNIQUE (name)');

        foreach (array_keys($this->tables) as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('institution_id'));
        }
    }
};
