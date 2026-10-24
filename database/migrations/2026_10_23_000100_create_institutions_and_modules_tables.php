<?php

use App\Support\ModuleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Arquitectura multiinstitucional: instituciones educativas, catálogo de
 * módulos y módulos por institución.
 * Crea la institución inicial con los datos de config/seguridad_escolar.php
 * para que los datos existentes queden asociados a ella (ver migración 000200).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 60)->unique();
            $table->string('code_prefix', 12)->unique();          // siglas usadas en los códigos de caso
            $table->string('amie_code', 20)->nullable()->unique(); // código AMIE del Ministerio de Educación
            $table->string('province', 60)->nullable();
            $table->string('canton', 80)->nullable();
            $table->string('parish', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('status', 20)->default('en_preparacion')->index();
            $table->boolean('is_default')->default(false);
            // Configuración local autorizada
            $table->boolean('allow_reports_without_contact')->default(true);
            $table->boolean('close_requires_followup')->default(false);
            $table->boolean('close_requires_measures_resolved')->default(false);
            $table->unsignedSmallInteger('public_report_hourly_limit')->default(5);
            $table->string('notes', 500)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_status_check CHECK (status IN ('en_preparacion','activa','suspendida','inactiva'))");
        DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_prefix_check CHECK (code_prefix ~ '^[A-Z0-9]{2,12}$')");
        DB::statement('ALTER TABLE institutions ADD CONSTRAINT institutions_limit_check CHECK (public_report_hourly_limit BETWEEN 1 AND 100)');
        DB::statement('CREATE UNIQUE INDEX institutions_single_default ON institutions (is_default) WHERE is_default = true');

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name', 120);
            $table->string('description', 500);
            $table->boolean('is_implemented')->default(false);
            $table->boolean('is_globally_enabled')->default(false);
            $table->jsonb('requires')->default('[]');
            $table->jsonb('permissions')->default('[]');
            $table->jsonb('config')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        // Un módulo no implementado no puede estar habilitado globalmente
        DB::statement('ALTER TABLE modules ADD CONSTRAINT modules_implemented_check CHECK (is_implemented OR NOT is_globally_enabled)');

        Schema::create('institution_module', function (Blueprint $table) {
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('is_authorized')->default(false); // lo decide la plataforma
            $table->boolean('is_enabled')->default(false);    // lo decide la institución
            $table->jsonb('config')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->primary(['institution_id', 'module_id']);
        });
        DB::statement('ALTER TABLE institution_module ADD CONSTRAINT institution_module_authorized_check CHECK (is_authorized OR NOT is_enabled)');

        // ---- Datos iniciales: institución por defecto y catálogo de módulos
        $name = config('seguridad_escolar.institucion.nombre', 'Unidad Educativa');
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) config('seguridad_escolar.institucion.siglas', 'UE'))) ?: 'UE';
        $institutionId = DB::table('institutions')->insertGetId([
            'name' => $name,
            'slug' => Str::slug($prefix),
            'code_prefix' => substr($prefix, 0, 12),
            'status' => 'activa',
            'is_default' => true,
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = 0;
        foreach (ModuleCatalog::MODULES as $key => $m) {
            $moduleId = DB::table('modules')->insertGetId([
                'key' => $key,
                'name' => $m['name'],
                'description' => $m['description'],
                'is_implemented' => $m['implemented'],
                'is_globally_enabled' => $m['implemented'],
                'requires' => json_encode($m['requires']),
                'permissions' => json_encode($m['permissions']),
                'sort_order' => $order += 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($m['implemented']) {
                DB::table('institution_module')->insert([
                    'institution_id' => $institutionId,
                    'module_id' => $moduleId,
                    'is_authorized' => true,
                    'is_enabled' => $m['default'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_module');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('institutions');
    }
};
