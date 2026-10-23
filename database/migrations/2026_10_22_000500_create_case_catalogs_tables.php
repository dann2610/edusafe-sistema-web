<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos configurables de gestión de casos:
 * categorías de incidente, tipos de medida y tipos de actuación (seguimiento).
 * Los estados del caso NO son catálogo editable: dependen de reglas de negocio
 * (App\Enums\CaseStatus) y se protegen con CHECK en la tabla incidents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120)->unique();
            $table->string('description', 500)->nullable();
            // Sugiere prioridad alta al registrar (no decide nada por sí sola)
            $table->boolean('suggests_high_priority')->default(false);
            $table->boolean('available_in_public_form')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('measure_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('kind', 15);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE measure_types ADD CONSTRAINT measure_types_kind_check CHECK (kind IN ('formativa','preventiva','proteccion'))");

        Schema::create('follow_up_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_types');
        Schema::dropIfExists('measure_types');
        Schema::dropIfExists('incident_categories');
    }
};
