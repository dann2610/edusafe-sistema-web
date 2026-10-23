<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de auditoría + inmutabilidad a nivel de base de datos.
 * El trigger prevent_modification() impide UPDATE y DELETE sobre:
 *   audit_logs, case_status_history y case_events.
 * Así ni la aplicación ni un usuario con acceso SQL ordinario puede alterar
 * el historial. Solo un superusuario podría deshabilitar el trigger, lo cual
 * queda fuera del alcance de los roles de la aplicación.
 * metadata NO debe contener contraseñas, tokens ni descripciones de denuncias.
 */
return new class extends Migration
{
    private array $protected = ['audit_logs', 'case_status_history', 'case_events'];

    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action', 60);
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('result', 10)->default('exito');
            $table->ipAddress('ip_address')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_result_check CHECK (result IN ('exito','denegado','fallido'))");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_modification() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Registro inmutable: no se permite % en %', TG_OP, TG_TABLE_NAME
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        foreach ($this->protected as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION prevent_modification()");
            // TRUNCATE no dispara triggers de fila; se bloquea con uno de sentencia.
            // (RefreshDatabase usa transacciones, no TRUNCATE, por lo que no interfiere)
            DB::unprepared("CREATE TRIGGER {$table}_no_truncate BEFORE TRUNCATE ON {$table}
                FOR EACH STATEMENT EXECUTE FUNCTION prevent_modification()");
        }
    }

    public function down(): void
    {
        foreach ($this->protected as $table) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$table}_immutable ON {$table}");
            DB::unprepared("DROP TRIGGER IF EXISTS {$table}_no_truncate ON {$table}");
        }
        Schema::dropIfExists('audit_logs');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_modification()');
    }
};
