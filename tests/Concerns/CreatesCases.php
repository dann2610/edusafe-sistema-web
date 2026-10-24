<?php

namespace Tests\Concerns;

use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExternalEntity;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\IncidentRegistrar;
use Illuminate\Http\UploadedFile;

/** Ayudantes para crear datos ficticios en las pruebas. */
trait CreatesCases
{
    protected function user(RoleSlug $role, array $attrs = []): User
    {
        $user = User::factory()->role($role)->create($attrs);
        if ($role === RoleSlug::Policia && ! isset($attrs['external_entity_id'])) {
            $user->forceFill(['external_entity_id' => ExternalEntity::where('type', 'policia')->value('id')])->save();
        }

        return $user->fresh();
    }

    protected function period(): AcademicPeriod
    {
        $institutionId = app(\App\Support\TenantContext::class)->id() ?? \App\Models\Institution::default()->id;
        $period = AcademicPeriod::current($institutionId);
        if (! $period) {
            $period = new AcademicPeriod(['name' => 'Prueba 2026', 'starts_on' => '2026-05-01', 'ends_on' => '2027-02-28', 'is_active' => true]);
            $period->institution_id = $institutionId;
            $period->save();
        }
        if (! $period->is_current) {
            $period->markAsCurrent();
        }

        return $period->fresh();
    }

    protected function course(): Course
    {
        $period = $this->period();

        return Course::withoutInstitutionScope()->firstOrCreate(
            ['institution_id' => $period->institution_id, 'academic_period_id' => $period->id, 'level' => '8.º EGB', 'parallel' => 'A', 'shift' => 'matutina'],
            ['is_active' => true],
        );
    }

    protected function enrolledStudent(): Student
    {
        $student = Student::factory()->create(['institution_id' => $this->period()->institution_id]);
        Enrollment::create(['student_id' => $student->id, 'course_id' => $this->course()->id, 'academic_period_id' => $this->period()->id]);

        return $student;
    }

    protected function category(string $code = 'ACOSO'): IncidentCategory
    {
        return IncidentCategory::withoutInstitutionScope()->whereNull('institution_id')->where('code', $code)->firstOrFail();
    }

    protected function incident(?User $reporter = null, array $data = [], array $participants = []): Incident
    {
        $reporter ??= $this->user(RoleSlug::Docente);

        return app(IncidentRegistrar::class)->registerInternal($reporter, $data + [
            'incident_category_id' => $this->category()->id,
            'description' => 'Descripción ficticia de prueba con suficiente detalle.',
            'immediate_risk' => false,
        ], $participants);
    }

    protected function fakePdf(string $name = 'evidencia.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
    }
}
