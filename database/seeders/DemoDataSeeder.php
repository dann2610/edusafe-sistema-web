<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ExternalEntity;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DATOS DE DEMOSTRACIÓN — solo para APP_ENV=local o testing.
 * - Correos con dominio reservado .test (no existen).
 * - Estudiantes ficticios identificados con el prefijo "DEMO-".
 * - Contraseña: DEMO_PASSWORD del .env o, si está vacía, una aleatoria por usuario
 *   que se muestra una sola vez en consola. Nunca hay contraseñas fijas en el código.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoDataSeeder solo puede ejecutarse en entornos local o testing.');
        }

        DB::transaction(function () {
            $period = AcademicPeriod::firstOrCreate(
                ['name' => '2026-2027 (DEMO)'],
                ['starts_on' => '2026-05-04', 'ends_on' => '2027-02-26', 'is_active' => true],
            );
            if (! AcademicPeriod::current()) {
                $period->markAsCurrent();
            }

            $police = ExternalEntity::where('type', 'policia')->first();
            $shared = (string) config('seguridad_escolar.demo.password');
            $credentials = [];

            $users = [
                ['admin.demo@uecrc.test', 'Administrador Demo', 'Soporte técnico', RoleSlug::Administrador],
                ['dece.demo@uecrc.test', 'Consejería DECE Demo', 'Profesional DECE', RoleSlug::Dece],
                ['docente.demo@uecrc.test', 'Docente Demo', 'Docente tutor', RoleSlug::Docente],
                ['docente2.demo@uecrc.test', 'Docente Demo Dos', 'Docente', RoleSlug::Docente],
                ['autoridad.demo@uecrc.test', 'Autoridad Demo', 'Rectorado', RoleSlug::Autoridad],
                ['policia.demo@uecrc.test', 'Agente Demo', 'Enlace policial', RoleSlug::Policia],
            ];

            $created = [];
            foreach ($users as [$email, $name, $position, $role]) {
                $user = User::where('email', $email)->first();
                if (! $user) {
                    $password = $shared !== '' ? $shared : Str::password(16);
                    $user = new User(['name' => $name, 'email' => $email, 'position' => $position, 'password' => $password]);
                    $credentials[] = [$email, $role->label(), $shared !== '' ? '(DEMO_PASSWORD del .env)' : $password];
                }
                $user->role_id = Role::where('slug', $role->value)->value('id');
                $user->external_entity_id = $role === RoleSlug::Policia ? $police?->id : null;
                $user->is_active = true;
                $user->email_verified_at ??= now();
                $user->save();
                $created[$email] = $user;
            }

            $courses = [];
            foreach ([['8.º EGB', 'A'], ['9.º EGB', 'A'], ['10.º EGB', 'B'], ['1.º BGU', 'A']] as $i => [$level, $parallel]) {
                $courses[] = Course::firstOrCreate(
                    ['academic_period_id' => $period->id, 'level' => $level, 'parallel' => $parallel, 'shift' => 'matutina'],
                    ['tutor_user_id' => $i === 0 ? $created['docente.demo@uecrc.test']->id : null, 'is_active' => true],
                );
            }

            // Estudiantes ficticios: nombres genéricos, sin relación con personas reales
            $first = ['Estudiante Alfa', 'Estudiante Beta', 'Estudiante Gamma', 'Estudiante Delta', 'Estudiante Épsilon', 'Estudiante Zeta'];
            $n = 1;
            foreach ($courses as $course) {
                foreach ($first as $j => $fname) {
                    $code = sprintf('DEMO-%04d', $n++);
                    $student = Student::firstOrCreate(
                        ['institutional_code' => $code],
                        ['first_names' => $fname, 'last_names' => 'Ficticio '.($j + 1), 'is_active' => true],
                    );
                    Enrollment::firstOrCreate(
                        ['student_id' => $student->id, 'academic_period_id' => $period->id],
                        ['course_id' => $course->id],
                    );
                }
            }

            if ($credentials && $this->command) {
                $this->command->warn('Usuarios DEMO creados (solo entorno local). Guarde estas credenciales: no se volverán a mostrar.');
                $this->command->table(['Correo', 'Rol', 'Contraseña'], $credentials);
            }
        });
    }
}
