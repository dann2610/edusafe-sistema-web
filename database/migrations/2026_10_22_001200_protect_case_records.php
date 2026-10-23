<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Protección de expedientes a nivel de base de datos:
 * - incidents: no se pueden eliminar (sí actualizar estado, prioridad, cierre).
 * - follow_ups: no se pueden modificar ni eliminar (las correcciones son registros nuevos).
 * Reutiliza la función prevent_modification() creada con la bitácora.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('CREATE TRIGGER incidents_no_delete BEFORE DELETE ON incidents
            FOR EACH ROW EXECUTE FUNCTION prevent_modification()');
        DB::unprepared('CREATE TRIGGER incidents_no_truncate BEFORE TRUNCATE ON incidents
            FOR EACH STATEMENT EXECUTE FUNCTION prevent_modification()');
        DB::unprepared('CREATE TRIGGER follow_ups_immutable BEFORE UPDATE OR DELETE ON follow_ups
            FOR EACH ROW EXECUTE FUNCTION prevent_modification()');
        DB::unprepared('CREATE TRIGGER follow_ups_no_truncate BEFORE TRUNCATE ON follow_ups
            FOR EACH STATEMENT EXECUTE FUNCTION prevent_modification()');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS incidents_no_delete ON incidents');
        DB::unprepared('DROP TRIGGER IF EXISTS incidents_no_truncate ON incidents');
        DB::unprepared('DROP TRIGGER IF EXISTS follow_ups_immutable ON follow_ups');
        DB::unprepared('DROP TRIGGER IF EXISTS follow_ups_no_truncate ON follow_ups');
    }
};
