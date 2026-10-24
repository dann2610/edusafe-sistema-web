<?php

namespace Database\Seeders;

use App\Enums\AssignmentRole;
use App\Enums\CaseStatus;
use App\Enums\RoleSlug;
use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\ExternalEntity;
use App\Models\FollowUpType;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\MeasureType;
use App\Models\User;
use App\Services\CaseWorkflow;
use App\Services\IncidentRegistrar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * CASOS DE DEMOSTRACIÓN — solo local/testing.
 * Todos los textos empiezan con "[DEMO]" y los involucrados son los estudiantes
 * ficticios DEMO-xxxx. Se crean con los servicios reales del sistema para que el
 * historial, la bitácora y las notificaciones sean coherentes; las fechas se
 * reparten en los últimos meses para que el dashboard muestre tendencias.
 */
class DemoIncidentsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoIncidentsSeeder solo puede ejecutarse en entornos local o testing.');
        }
        if (Incident::where('description', 'like', '[DEMO]%')->exists()) {
            $this->command?->info('Los casos DEMO ya existen.');

            return;
        }

        $registrar = app(IncidentRegistrar::class);
        $workflow = app(CaseWorkflow::class);
        $institution = \App\Models\Institution::default();

        $dece = User::where('email', 'dece.demo@uecrc.test')->firstOrFail();
        $docentes = User::active()->ofInstitution($institution->id)->withRole(RoleSlug::Docente)->get();
        $autoridad = User::where('email', 'autoridad.demo@uecrc.test')->first();
        $reporters = $docentes->concat(array_filter([$dece, $autoridad]))->values(); // concat no modifica $docentes

        $period = AcademicPeriod::current($institution->id);
        $enrollments = Enrollment::with('student')->where('academic_period_id', $period?->id)->orderBy('id')->get();
        $cat = IncidentCategory::whereNull('institution_id')->pluck('id', 'code');
        $types = FollowUpType::whereNull('institution_id')->pluck('id', 'name');
        $measures = MeasureType::whereNull('institution_id')->pluck('id', 'name');
        $police = ExternalEntity::whereNull('institution_id')->where('type', 'policia')->first();
        $junta = ExternalEntity::whereNull('institution_id')->where('type', 'junta_proteccion')->first();

        // [categoría, días atrás, estado final, riesgo inmediato, público, texto]
        $plan = [
            ['ACOSO', 230, 'cerrado', false, false, 'Burlas reiteradas en el recreo hacia un compañero por su forma de hablar.'],
            ['VIOL_VERBAL', 220, 'cerrado', false, false, 'Insultos durante la clase de educación física entre dos estudiantes.'],
            ['CIBERACOSO', 205, 'cerrado', false, false, 'Publicación de memes ofensivos sobre una estudiante en un grupo de mensajería del curso.'],
            ['VIOL_FISICA', 190, 'cerrado', false, false, 'Empujones a la salida de clases que terminaron en una caída sin lesiones graves.'],
            ['ACOSO', 175, 'cerrado', false, false, 'Exclusión sistemática de una estudiante en los trabajos grupales.'],
            ['AMENAZA', 160, 'derivado', true, false, 'Mensaje con amenazas dirigido a un estudiante, recibido fuera del horario escolar.'],
            ['DISCRIMINACION', 150, 'cerrado', false, false, 'Comentarios discriminatorios sobre el origen de un estudiante nuevo.'],
            ['VIOL_VERBAL', 140, 'cerrado', false, true, 'Reporte recibido por el formulario público sobre gritos e insultos en el bar escolar.'],
            ['CIBERACOSO', 128, 'en_seguimiento', false, false, 'Suplantación de perfil en una red social para ridiculizar a un estudiante.'],
            ['ACOSO', 115, 'cerrado', false, false, 'Apodos ofensivos persistentes en el aula durante varias semanas.'],
            ['SUSTANCIAS', 105, 'derivado', false, false, 'Se comunicó la posible presencia de cigarrillos electrónicos en los baños.'],
            ['VIOL_FISICA', 95, 'en_seguimiento', false, false, 'Pelea en el patio durante el recreo; intervino el docente de turno.'],
            ['ACOSO', 84, 'en_seguimiento', false, false, 'Una estudiante manifiesta que compañeros le esconden sus pertenencias a diario.'],
            ['CIBERACOSO', 72, 'asignado', false, true, 'Reporte público: difusión de capturas de conversaciones privadas.'],
            ['VIOL_VERBAL', 63, 'cerrado', false, false, 'Discusión con insultos entre estudiantes de cursos distintos.'],
            ['OTRA', 55, 'cerrado', false, false, 'Estudiante que se queda solo frecuentemente después de clases sin supervisión.'],
            ['AMENAZA', 46, 'en_seguimiento', true, false, 'Amenaza verbal de agresión a la salida de la institución.'],
            ['ACOSO', 38, 'asignado', false, false, 'Compañeros imitan y se burlan de la discapacidad de un estudiante.'],
            ['CIBERACOSO', 30, 'en_revision', false, false, 'Encuesta en línea creada para votar por «el más feo» del curso.'],
            ['VIOL_FISICA', 24, 'requiere_informacion', false, true, 'Reporte público sobre golpes en el transporte escolar; faltan datos del curso.'],
            ['DISCRIMINACION', 18, 'asignado', false, false, 'Comentarios ofensivos en clase sobre la religión de una compañera.'],
            ['ACOSO', 12, 'en_revision', false, false, 'Mensajes anónimos en el pupitre de una estudiante.'],
            ['VIOL_VERBAL', 8, 'registrado', false, false, 'Insultos entre estudiantes durante el cambio de hora.'],
            ['AMENAZA', 4, 'registrado', true, true, 'Reporte público con riesgo inmediato: amenaza de agresión prevista para la salida.'],
            ['CIBERACOSO', 2, 'registrado', false, false, 'Grupo de mensajería creado para excluir y burlarse de un estudiante.'],
            ['OTRA', 1, 'registrado', false, false, 'Estudiante comenta sentirse inseguro en el camino hacia la institución.'],
        ];

        $n = 0;
        $reference = Carbon::now()->copy(); // fecha fija: cada caso se ubica N días antes de hoy
        foreach ($plan as [$code, $daysAgo, $finalStatus, $risk, $public, $text]) {
            $n++;
            $base = $reference->copy()->subDays($daysAgo)->setTime(8 + ($n % 6), 15 + ($n * 7) % 40);
            Carbon::setTestNow($base);

            $enrollment = $enrollments->isNotEmpty() ? $enrollments[($n * 5) % $enrollments->count()] : null;
            $other = $enrollments->isNotEmpty() ? $enrollments[($n * 5 + 3) % $enrollments->count()] : null;
            $data = [
                'incident_category_id' => $cat[$code],
                'course_id' => $public ? null : $enrollment?->course_id,
                'occurred_at' => $base->copy()->subHours(3)->toDateTimeString(),
                'location' => ['Patio central', 'Aula', 'Redes sociales', 'Salida principal', 'Bar escolar', 'Cancha'][$n % 6],
                'description' => '[DEMO] '.$text,
                'immediate_risk' => $risk,
            ];

            if ($public) {
                [$incident] = $registrar->registerPublic($institution, $data + [
                    'reporter_relationship' => $n % 2 ? 'estudiante' : 'representante',
                    'reporter_mode' => 'sin_contacto', 'is_ongoing' => 'no_sabe', 'authority_informed' => 'no',
                ], [['role' => 'afectado', 'non_student_description' => 'Estudiante del curso (sin nombre)']]);
            } else {
                $participants = [];
                if ($enrollment) {
                    $participants[] = ['student_id' => $enrollment->student_id, 'role' => 'afectado'];
                }
                if ($other && $n % 2 === 0) {
                    $participants[] = ['student_id' => $other->student_id, 'role' => 'senalado'];
                }
                $incident = $registrar->registerInternal($reporters[$n % $reporters->count()], $data, $participants);
            }

            $this->advance($workflow, $incident, $finalStatus, $dece, $docentes, $types, $measures, $police, $junta, $base, $n);
        }

        Carbon::setTestNow();

        // Tres casos en la segunda institución DEMO (para comprobar el aislamiento)
        $second = \App\Models\Institution::where('code_prefix', 'UEDEMO')->first();
        $secondDocente = User::where('email', 'docente@uedemo.test')->first();
        if ($second && $secondDocente) {
            foreach (['ACOSO' => '[DEMO] Caso de la segunda institución: burlas en el recreo.', 'CIBERACOSO' => '[DEMO] Caso de la segunda institución: mensajes ofensivos en redes.', 'VIOL_VERBAL' => '[DEMO] Caso de la segunda institución: insultos en el aula.'] as $code => $text) {
                $registrar->registerInternal($secondDocente, ['incident_category_id' => $cat[$code], 'description' => $text, 'immediate_risk' => false]);
            }
        }

        // Los avisos antiguos de la demostración quedan como leídos
        DB::table('notifications')->where('created_at', '<', now()->subDays(10))->update(['read_at' => now()]);

        $this->command?->info("Casos DEMO creados: {$n}");
    }

    private function advance(CaseWorkflow $wf, Incident $incident, string $final, User $dece, $docentes, $types, $measures, ?ExternalEntity $police, ?ExternalEntity $junta, Carbon $base, int $n): void
    {
        $order = ['registrado', 'en_revision', 'asignado', 'en_seguimiento', 'derivado', 'cerrado'];
        $target = array_search($final, $order, true);
        $step = fn (int $hours) => Carbon::setTestNow($base->copy()->addHours($hours));

        if ($final === 'registrado') {
            return;
        }

        $step(2);
        $wf->transition($incident, CaseStatus::EnRevision, $dece);
        $incident->refresh();

        if ($final === 'requiere_informacion') {
            $step(5);
            $wf->transition($incident, CaseStatus::RequiereInformacion, $dece, '[DEMO] Se solicita a la familia confirmar el curso y la fecha.');

            return;
        }
        if ($target === false || $target < 2) {
            return;
        }

        $step(6);
        $assignee = $n % 3 === 0 ? $dece : $docentes[$n % $docentes->count()];
        $wf->assign($incident, $assignee, AssignmentRole::Principal, $dece, '[DEMO] Dar acompañamiento y registrar las entrevistas.');
        $incident->refresh();
        if ($final === 'asignado') {
            return;
        }

        $step(30);
        $wf->addFollowUp($incident, [
            'follow_up_type_id' => $types['Entrevista con estudiante'],
            'performed_at' => now()->toDateTimeString(),
            'description' => '[DEMO] Entrevista inicial con el estudiante afectado. Se escucha su relato y se acuerdan pautas de seguridad.',
            'next_action' => 'Reunión con representantes',
            'next_follow_up_on' => $n > 20 ? now()->addDays(3)->toDateString() : null,
        ], $assignee);
        $incident->refresh();

        $step(54);
        $wf->addMeasure($incident, [
            'measure_type_id' => $measures[$n % 2 ? 'Acompañamiento psicoeducativo' : 'Comunicación y reunión con representantes'],
            'description' => '[DEMO] Medida acordada por el DECE para acompañar a las partes.',
            'responsible_user_id' => $assignee->id,
            'starts_on' => now()->toDateString(),
            'review_on' => now()->addDays(15)->toDateString(),
        ], $dece);

        if ($final === 'en_seguimiento') {
            return;
        }

        if (in_array($final, ['derivado'], true) || ($final === 'cerrado' && $n % 4 === 0)) {
            $step(72);
            $entity = $incident->category->code === 'SUSTANCIAS' || $incident->category->code === 'AMENAZA' ? $police : $junta;
            if ($entity) {
                $wf->addReferral($incident, [
                    'external_entity_id' => $entity->id,
                    'referred_on' => now()->toDateString(),
                    'reason' => '[DEMO] Derivación conforme al protocolo institucional vigente.',
                    'channel' => 'oficio',
                    'reference_number' => 'UECRC-DECE-'.now()->format('Y').'-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                    'shared_summary' => '[DEMO] Se informa la situación reportada y las medidas de protección adoptadas por la institución.',
                ], $dece);
                $incident->refresh();
            }
            if ($final === 'derivado') {
                return;
            }
            $step(120);
            $wf->transition($incident, CaseStatus::EnSeguimiento, $dece);
            $incident->refresh();
        }

        $step(24 * (5 + $n % 10));
        foreach ($incident->measures as $m) {
            $wf->updateMeasure($m, ['compliance_status' => 'cumplida'], $dece);
        }
        $wf->transition($incident, CaseStatus::Cerrado, $dece,
            '[DEMO] Se cumplieron las medidas y no se registran nuevos hechos.',
            '[DEMO] Caso atendido con acompañamiento del DECE. Las partes cumplieron los acuerdos de convivencia.');
    }
}
