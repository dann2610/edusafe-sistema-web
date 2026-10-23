<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Actuaciones sobre el caso: seguimientos, medidas y derivaciones.
 * Los seguimientos no se editan: una corrección es un nuevo registro que
 * referencia al original (corrects_follow_up_id), conservando la trazabilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->foreignId('follow_up_type_id')->constrained('follow_up_types')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('performed_at');
            $table->text('description');
            $table->text('observations')->nullable();
            $table->string('next_action', 500)->nullable();
            $table->date('next_follow_up_on')->nullable();
            $table->foreignId('corrects_follow_up_id')->nullable()->constrained('follow_ups')->restrictOnDelete();
            $table->string('correction_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['incident_id', 'performed_at']);
            $table->index('next_follow_up_on');
        });
        DB::statement('ALTER TABLE follow_ups ADD CONSTRAINT follow_ups_correction_reason_check CHECK (corrects_follow_up_id IS NULL OR correction_reason IS NOT NULL)');

        Schema::create('protective_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->foreignId('measure_type_id')->constrained('measure_types')->restrictOnDelete();
            $table->text('description');
            $table->foreignId('responsible_user_id')->constrained('users')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('review_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('compliance_status', 15)->default('pendiente');
            $table->text('observations')->nullable();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('incident_id');
            $table->index('compliance_status');
        });
        DB::statement("ALTER TABLE protective_measures ADD CONSTRAINT protective_measures_compliance_check CHECK (compliance_status IN
            ('pendiente','en_ejecucion','cumplida','incumplida','suspendida'))");
        DB::statement('ALTER TABLE protective_measures ADD CONSTRAINT protective_measures_dates_check CHECK (ends_on IS NULL OR ends_on >= starts_on)');

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->foreignId('external_entity_id')->constrained('external_entities')->restrictOnDelete();
            $table->date('referred_on');
            $table->text('reason');
            $table->string('channel', 20);
            $table->string('reference_number', 80)->nullable();
            // Información delimitada que se comparte con la entidad externa
            $table->text('shared_summary')->nullable();
            $table->text('observations')->nullable();
            $table->string('management_status', 15)->default('enviada');
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('incident_id');
            $table->index(['external_entity_id', 'management_status']);
        });
        DB::statement("ALTER TABLE referrals ADD CONSTRAINT referrals_channel_check CHECK (channel IN
            ('oficio','correo_institucional','presencial','telefonico','plataforma_oficial','otro'))");
        DB::statement("ALTER TABLE referrals ADD CONSTRAINT referrals_status_check CHECK (management_status IN
            ('enviada','recibida','en_tramite','respondida','finalizada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('protective_measures');
        Schema::dropIfExists('follow_ups');
    }
};
