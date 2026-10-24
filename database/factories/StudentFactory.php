<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Estudiantes ficticios para pruebas (prefijo TEST-).
 *
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'institution_id' => fn () => app(\App\Support\TenantContext::class)->id() ?? \App\Models\Institution::default()?->id,
            'institutional_code' => 'TEST-'.fake()->unique()->numerify('######'),
            'first_names' => 'Estudiante '.fake()->unique()->numerify('###'),
            'last_names' => 'Ficticio',
            'is_active' => true,
        ];
    }
}
