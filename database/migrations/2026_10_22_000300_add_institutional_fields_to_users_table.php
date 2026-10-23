<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campos institucionales del usuario.
 * - role_id: rol único del usuario.
 * - external_entity_id: obligatorio solo para personal policial / entidad externa;
 *   limita su acceso a los casos derivados a esa entidad.
 * - Las cuentas no se eliminan: se desactivan (is_active / deactivated_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('external_entity_id')->nullable()->after('role_id')->constrained('external_entities')->restrictOnDelete();
            $table->string('position', 120)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password')->index();
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->timestamp('deactivated_at')->nullable()->after('last_login_at');
        });

        // Emails únicos sin distinguir mayúsculas
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (LOWER(email))');

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('external_entity_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['position', 'is_active', 'must_change_password', 'last_login_at', 'deactivated_at']);
        });
    }
};
