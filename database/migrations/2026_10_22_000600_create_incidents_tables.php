<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Incidentes (casos) y personas involucradas.
 * - code: generado por el sistema con la secuencia incident_code_seq (único).
 * - Los casos nunca se eliminan físicamente (FK restrict).
 * - Reportes públicos: reporter_user_id nulo; datos de contacto opcionales.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS incident_code_seq START 1');

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('incident_category_id')->constrained('incident_categories')->restrictOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->restrictOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->restrictOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->boolean('occurred_at_is_approximate')->default(true);
            $table->string('location', 255)->nullable();
            $table->text('description');
            $table->boolean('immediate_risk')->default(false);
            $table->string('status', 25)->default('registrado');
            $table->string('priority', 10)->default('normal');
            $table->string('source', 10);                         // interno | publico
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reporter_relationship', 20)->nullable();
            $table->string('reporter_contact_name', 150)->nullable();
            $table->string('reporter_contact', 150)->nullable();
            $table->boolean('privacy_notice_accepted')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_summary')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
            $table->index(['academic_period_id', 'status']);
            $table->index('reporter_user_id');
        });

        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_status_check CHECK (status IN
            ('registrado','en_revision','requiere_informacion','asignado','en_seguimiento','derivado','cerrado'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_priority_check CHECK (priority IN ('normal','alta','urgente'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_source_check CHECK (source IN ('interno','publico'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_reporter_relationship_check CHECK (reporter_relationship IS NULL OR reporter_relationship IN
            ('estudiante','representante','docente','personal','otro'))");
        // Un reporte interno siempre tiene usuario registrador
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_internal_reporter_check CHECK (source = 'publico' OR reporter_user_id IS NOT NULL)");
        // Un caso cerrado debe tener fecha de cierre
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_closed_at_check CHECK (status <> 'cerrado' OR closed_at IS NOT NULL)");

        Schema::create('incident_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->restrictOnDelete();
            // Para personas que no son estudiantes: descripción genérica, sin datos identificativos innecesarios
            $table->string('non_student_description', 150)->nullable();
            $table->string('role', 15);
            $table->string('notes', 500)->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('incident_id');
            $table->index('student_id');
        });
        DB::statement("ALTER TABLE incident_participants ADD CONSTRAINT incident_participants_role_check CHECK (role IN ('afectado','senalado','testigo','otro'))");
        DB::statement('ALTER TABLE incident_participants ADD CONSTRAINT incident_participants_identity_check CHECK (student_id IS NOT NULL OR non_student_description IS NOT NULL)');
        DB::statement('CREATE UNIQUE INDEX incident_participants_student_role_unique ON incident_participants (incident_id, student_id, role) WHERE student_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_participants');
        Schema::dropIfExists('incidents');
        DB::statement('DROP SEQUENCE IF EXISTS incident_code_seq');
    }
};
