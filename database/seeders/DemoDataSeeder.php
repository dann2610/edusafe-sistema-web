<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExternalEntity;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DATOS DE DEMOSTRACIÓN — solo para APP_ENV=local o testing.
 * - Institución principal (la configurada) y una segunda institución DEMO para
 *   comprobar el aislamiento de datos.
 * - Correos con dominio reservado .test; estudiantes ficticios con prefijo DEMO-.
 * - Contraseña: DEMO_PASSWORD del .env o, si está vacía, una aleatoria por usuario.
 */
class DemoDataSeeder extends Seeder
{
    private array $credentials = [];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoDataSeeder solo puede ejecutarse en entornos local o testing.');
        }

        DB::transaction(function () {
            $main = Institution::default();
            $main->fill([
                'province' => $main->province ?? 'El Oro',
                'canton' => $main->canton ?? 'Machala',
                'phone' => $main->phone ?? '07 000 0000',
                'email' => $main->email ?? 'contacto@uecrc.test',
            ])->save();

            $this->user(null, 'superadmin.demo@uecrc.test', 'Superadministrador Demo', 'Plataforma', RoleSlug::Superadmin);
            $this->seedInstitution($main, 'uecrc', [
                ['admin.demo', 'Administrador Demo', 'Administración institucional', RoleSlug::Administrador],
                ['dece.demo', 'Consejería DECE Demo', 'Profesional DECE', RoleSlug::Dece],
                ['docente.demo', 'Docente Demo', 'Docente tutor', RoleSlug::Docente],
                ['docente2.demo', 'Docente Demo Dos', 'Docente', RoleSlug::Docente],
                ['autoridad.demo', 'Autoridad Demo', 'Rectorado', RoleSlug::Autoridad],
                ['policia.demo', 'Agente Demo', 'Enlace policial', RoleSlug::Policia],
            ], [['8.º EGB', 'A'], ['9.º EGB', 'A'], ['10.º EGB', 'B'], ['1.º BGU', 'A']], 'DEMO');

            // Segunda institución para demostrar el aislamiento entre escuelas
            $second = Institution::where('code_prefix', 'UEDEMO')->first();
            if (! $second) {
                $second = new Institution([
                    'name' => 'Unidad Educativa Demostración Dos (DEMO)', 'province' => 'Guayas', 'canton' => 'Guayaquil',
                    'email' => 'contacto@uedemo.test', 'allow_reports_without_contact' => false,
                ]);
                $second->forceFill(['slug' => 'uedemo', 'code_prefix' => 'UEDEMO', 'status' => 'activa', 'activated_at' => now()])->save();
                app(ModuleService::class)->provision($second);
            }
            $this->seedInstitution($second, 'uedemo', [
                ['admin', 'Administración Demo Dos', 'Administración institucional', RoleSlug::Administrador],
                ['dece', 'DECE Demo Dos', 'Profesional DECE', RoleSlug::Dece],
                ['docente', 'Docente Institución Dos', 'Docente', RoleSlug::Docente],
            ], [['7.º EGB', 'A'], ['2.º BGU', 'A']], 'DEMO2');

            if ($this->credentials && $this->command) {
                $this->command->warn('Usuarios DEMO creados (solo entorno local). Guarde estas credenciales: no se volverán a mostrar.');
                $this->command->table(['Correo', 'Rol', 'Contraseña'], $this->credentials);
            }
        });
    }

    private function seedInstitution(Institution $institution, string $domain, array $users, array $courses, string $codePrefix): void
    {
        $period = AcademicPeriod::withoutInstitutionScope()->firstOrCreate(
            ['institution_id' => $institution->id, 'name' => '2026-2027 (DEMO)'],
            ['starts_on' => '2026-05-04', 'ends_on' => '2027-02-26', 'is_active' => true],
        );
        if (! AcademicPeriod::current($institution->id)) {
            $period->markAsCurrent();
        }

        $created = [];
        foreach ($users as [$local, $name, $position, $role]) {
            $created[$local] = $this->user($institution, "{$local}@{$domain}.test", $name, $position, $role);
        }

        $courseModels = [];
        foreach ($courses as $i => [$level, $parallel]) {
            $tutor = collect($created)->first(fn ($u) => $u->hasRole(RoleSlug::Docente));
            $courseModels[] = Course::withoutInstitutionScope()->firstOrCreate(
                ['institution_id' => $institution->id, 'academic_period_id' => $period->id, 'level' => $level, 'parallel' => $parallel, 'shift' => 'matutina'],
                ['tutor_user_id' => $i === 0 ? $tutor?->id : null, 'is_active' => true],
            );
        }

        // Estudiantes ficticios: nombres genéricos, sin relación con personas reales
        $first = ['Estudiante Alfa', 'Estudiante Beta', 'Estudiante Gamma', 'Estudiante Delta', 'Estudiante Épsilon', 'Estudiante Zeta'];
        $n = 1;
        foreach ($courseModels as $course) {
            foreach ($first as $j => $fname) {
                $student = Student::withoutInstitutionScope()->firstOrCreate(
                    ['institution_id' => $institution->id, 'institutional_code' => sprintf('%s-%04d', $codePrefix, $n++)],
                    ['first_names' => $fname, 'last_names' => 'Ficticio '.($j + 1), 'is_active' => true],
                );
                Enrollment::firstOrCreate(['student_id' => $student->id, 'academic_period_id' => $period->id], ['course_id' => $course->id]);
            }
        }
    }

    private function user(?Institution $institution, string $email, string $name, string $position, RoleSlug $role): User
    {
        $shared = (string) config('seguridad_escolar.demo.password');
        $user = User::where('email', $email)->first();
        if (! $user) {
            $password = $shared !== '' ? $shared : Str::password(16);
            $user = new User(['name' => $name, 'email' => $email, 'position' => $position, 'password' => $password]);
            $this->credentials[] = [$email, $role->label(), $shared !== '' ? '(DEMO_PASSWORD del .env)' : $password];
        }
        $user->institution_id = $institution?->id;
        $user->role_id = Role::where('slug', $role->value)->value('id');
        $user->external_entity_id = $role === RoleSlug::Policia
            ? ExternalEntity::withoutInstitutionScope()->whereNull('institution_id')->where('type', 'policia')->value('id') : null;
        $user->is_active = true;
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
