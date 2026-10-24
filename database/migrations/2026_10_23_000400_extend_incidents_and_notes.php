<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos ampliados del reporte (formulario por pasos) y notas de quien reporta.
 * - occurred_at NULL = fecha desconocida; occurred_time_known=false = hora desconocida.
 * - public_access_hash: hash de la clave de consulta de estado (nunca la clave).
 * - incident_notes: información adicional aportada por quien registró el caso (inmutable).
 */
return new class extends Migration
{
    private const EVENT_TYPES = "'creado','participante_agregado','asignado','asignacion_liberada','estado_cambiado','seguimiento','correccion',
        'medida_registrada','medida_actualizada','derivacion','derivacion_actualizada','adjunto','cerrado','reabierto'";

    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $t) {
            $t->boolean('occurred_time_known')->default(true)->after('occurred_at_is_approximate');
            $t->string('is_ongoing', 10)->nullable()->after('immediate_risk');
            $t->string('authority_informed', 10)->nullable()->after('is_ongoing');
            $t->string('authority_informed_detail', 150)->nullable()->after('authority_informed');
            $t->string('reporter_mode', 15)->nullable()->after('reporter_relationship');
            $t->jsonb('extra_data')->nullable()->after('description');
            $t->string('public_access_hash', 255)->nullable();
        });
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_ongoing_check CHECK (is_ongoing IS NULL OR is_ongoing IN ('si','no','no_sabe'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_authority_check CHECK (authority_informed IS NULL OR authority_informed IN ('si','no','no_sabe'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_reporter_mode_check CHECK (reporter_mode IS NULL OR reporter_mode IN ('con_contacto','sin_contacto'))");

        Schema::create('incident_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->text('body');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['incident_id', 'created_at']);
        });
        DB::unprepared('CREATE TRIGGER incident_notes_immutable BEFORE UPDATE OR DELETE ON incident_notes FOR EACH ROW EXECUTE FUNCTION prevent_modification()');

        DB::statement('ALTER TABLE case_events DROP CONSTRAINT case_events_type_check');
        DB::statement('ALTER TABLE case_events ADD CONSTRAINT case_events_type_check CHECK (event_type IN ('.self::EVENT_TYPES.",'informacion_adicional','escalado'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE case_events DROP CONSTRAINT case_events_type_check');
        DB::statement('ALTER TABLE case_events ADD CONSTRAINT case_events_type_check CHECK (event_type IN ('.self::EVENT_TYPES.'))');
        DB::unprepared('DROP TRIGGER IF EXISTS incident_notes_immutable ON incident_notes');
        Schema::dropIfExists('incident_notes');
        foreach (['incidents_ongoing_check', 'incidents_authority_check', 'incidents_reporter_mode_check'] as $c) {
            DB::statement("ALTER TABLE incidents DROP CONSTRAINT IF EXISTS {$c}");
        }
        Schema::table('incidents', fn (Blueprint $t) => $t->dropColumn([
            'occurred_time_known', 'is_ongoing', 'authority_informed', 'authority_informed_detail', 'reporter_mode', 'extra_data', 'public_access_hash',
        ]));
    }
};
