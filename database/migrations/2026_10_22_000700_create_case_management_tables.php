<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión del caso:
 * - case_assignments: responsables (se liberan con unassigned_at, no se borran).
 * - case_status_history: cada cambio de estado con su justificación (inmutable).
 * - case_events: cronología unificada del caso (inmutable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('assignment_role', 15)->default('principal');
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->string('notes', 500)->nullable();
            $table->timestamp('unassigned_at')->nullable();
            $table->foreignId('unassigned_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('unassignment_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'unassigned_at']);
        });
        DB::statement("ALTER TABLE case_assignments ADD CONSTRAINT case_assignments_role_check CHECK (assignment_role IN ('principal','apoyo'))");
        // Una persona no puede tener dos asignaciones activas en el mismo caso
        DB::statement('CREATE UNIQUE INDEX case_assignments_active_unique ON case_assignments (incident_id, user_id) WHERE unassigned_at IS NULL');

        Schema::create('case_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->string('from_status', 25)->nullable();
            $table->string('to_status', 25);
            $table->foreignId('changed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('justification')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['incident_id', 'created_at']);
        });

        Schema::create('case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->string('event_type', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('summary', 500);
            $table->nullableMorphs('subject');
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['incident_id', 'created_at']);
        });
        DB::statement("ALTER TABLE case_events ADD CONSTRAINT case_events_type_check CHECK (event_type IN
            ('creado','participante_agregado','asignado','asignacion_liberada','estado_cambiado','seguimiento','correccion',
             'medida_registrada','medida_actualizada','derivacion','derivacion_actualizada','adjunto','cerrado','reabierto'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('case_events');
        Schema::dropIfExists('case_status_history');
        Schema::dropIfExists('case_assignments');
    }
};
