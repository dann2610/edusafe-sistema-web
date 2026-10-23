<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidencias adjuntas. El archivo vive en storage/app/private con nombre
 * aleatorio; la descarga pasa siempre por un controlador con autorización.
 * incident_id se guarda siempre para verificar permisos sobre el caso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->nullableMorphs('attachable'); // Incident, FollowUp, Referral...
            $table->string('original_name', 255);
            $table->string('disk', 20)->default('local');
            $table->string('stored_path', 255)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('incident_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
