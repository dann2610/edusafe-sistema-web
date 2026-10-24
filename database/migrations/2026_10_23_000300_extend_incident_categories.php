<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Categorías configurables:
 * - default_priority reemplaza a suggests_high_priority (se migra el valor).
 * - default_assignee_user_id: responsable inicial (solo categorías locales).
 * - incident_category_fields: campos adicionales tipados. Los reportes guardan
 *   una copia de etiqueta y valor (incidents.extra_data), por lo que cambiar o
 *   desactivar un campo no altera los reportes antiguos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_categories', function (Blueprint $t) {
            $t->string('default_priority', 10)->default('normal')->after('description');
            $t->foreignId('default_assignee_user_id')->nullable()->after('default_priority')->constrained('users')->nullOnDelete();
        });
        DB::table('incident_categories')->where('suggests_high_priority', true)->update(['default_priority' => 'alta']);
        Schema::table('incident_categories', fn (Blueprint $t) => $t->dropColumn('suggests_high_priority'));
        DB::statement("ALTER TABLE incident_categories ADD CONSTRAINT incident_categories_priority_check CHECK (default_priority IN ('normal','alta','urgente'))");
        // Una categoría global no puede tener un responsable de una institución concreta
        DB::statement('ALTER TABLE incident_categories ADD CONSTRAINT incident_categories_assignee_scope_check CHECK (institution_id IS NOT NULL OR default_assignee_user_id IS NULL)');

        Schema::create('incident_category_fields', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_category_id')->constrained('incident_categories')->restrictOnDelete();
            $t->string('key', 40);
            $t->string('label', 120);
            $t->string('type', 15);
            $t->jsonb('options')->nullable();
            $t->boolean('is_required')->default(false);
            $t->boolean('show_in_public_form')->default(true);
            $t->string('help_text', 255)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['incident_category_id', 'key']);
        });
        DB::statement("ALTER TABLE incident_category_fields ADD CONSTRAINT incident_category_fields_type_check CHECK (type IN ('texto','texto_largo','fecha','hora','lista','casilla'))");
        DB::statement("ALTER TABLE incident_category_fields ADD CONSTRAINT incident_category_fields_key_check CHECK (key ~ '^[a-z][a-z0-9_]{1,39}$')");
        DB::statement("ALTER TABLE incident_category_fields ADD CONSTRAINT incident_category_fields_options_check CHECK (type <> 'lista' OR (options IS NOT NULL AND jsonb_array_length(options) BETWEEN 2 AND 20))");
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_category_fields');
        Schema::table('incident_categories', fn (Blueprint $t) => $t->boolean('suggests_high_priority')->default(false));
        DB::table('incident_categories')->where('default_priority', '!=', 'normal')->update(['suggests_high_priority' => true]);
        DB::statement('ALTER TABLE incident_categories DROP CONSTRAINT IF EXISTS incident_categories_priority_check');
        DB::statement('ALTER TABLE incident_categories DROP CONSTRAINT IF EXISTS incident_categories_assignee_scope_check');
        Schema::table('incident_categories', function (Blueprint $t) {
            $t->dropConstrainedForeignId('default_assignee_user_id');
            $t->dropColumn('default_priority');
        });
    }
};
