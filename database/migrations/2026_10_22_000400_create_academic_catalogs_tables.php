<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos académicos: periodos lectivos, cursos, estudiantes y matrículas.
 * Minimización de datos: del estudiante solo se guarda un código institucional
 * y nombres. No se almacena cédula, dirección, teléfono ni datos de salud.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();          // p. ej. "2026-2027 Costa"
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE academic_periods ADD CONSTRAINT academic_periods_dates_check CHECK (ends_on > starts_on)');
        // Solo puede existir un periodo vigente
        DB::statement('CREATE UNIQUE INDEX academic_periods_single_current ON academic_periods (is_current) WHERE is_current = true');

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->restrictOnDelete();
            $table->string('level', 60);                    // p. ej. "8.º EGB", "1.º BGU"
            $table->string('parallel', 5);                  // "A", "B"
            $table->string('shift', 15)->default('matutina');
            $table->foreignId('tutor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['academic_period_id', 'level', 'parallel', 'shift']);
        });
        DB::statement("ALTER TABLE courses ADD CONSTRAINT courses_shift_check CHECK (shift IN ('matutina','vespertina','nocturna'))");

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('institutional_code', 30)->unique();
            $table->string('first_names', 100);
            $table->string('last_names', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['last_names', 'first_names']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->restrictOnDelete();
            $table->timestamps();
            // Un estudiante tiene una sola matrícula por periodo
            $table->unique(['student_id', 'academic_period_id']);
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('academic_periods');
    }
};
