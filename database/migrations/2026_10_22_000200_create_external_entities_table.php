<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entidades externas a las que se derivan casos (Policía, Fiscalía, Junta
 * Cantonal de Protección de Derechos, etc.). Solo datos institucionales de contacto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('type', 30);
            $table->string('institutional_phone', 30)->nullable();
            $table->string('institutional_email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('notes', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE external_entities ADD CONSTRAINT external_entities_type_check
            CHECK (type IN ('policia','fiscalia','junta_proteccion','salud','distrito_educativo','dinapen','otra'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('external_entities');
    }
};
