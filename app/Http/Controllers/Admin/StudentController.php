<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditResult;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Estudiantes y su matrícula en el periodo vigente.
 * Minimización: solo código institucional, nombres y apellidos.
 * Importación CSV para cargas masivas (codigo;nombres;apellidos;curso_id).
 */
class StudentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $current = AcademicPeriod::current();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'curso' => ['nullable', 'integer'],
        ]);

        $students = Student::query()
            ->with(['enrollments' => fn ($q) => $q->where('academic_period_id', $current?->id)->with('course')])
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term)).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(first_names) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(last_names) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(institutional_code) LIKE ?', [$like]));
            })
            ->when($filters['curso'] ?? null, fn ($q, $curso) => $q->whereHas('enrollments', fn ($e) => $e->where('course_id', $curso)))
            ->orderByDesc('is_active')->orderBy('last_names')->orderBy('first_names')
            ->paginate(25)->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'filters' => $filters,
            'current' => $current,
            'courses' => $this->currentCourses($current),
        ]);
    }

    public function create(): View
    {
        $current = AcademicPeriod::current();

        return view('admin.students.form', ['student' => null, 'current' => $current, 'courses' => $this->currentCourses($current), 'courseId' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $student = Student::create([
                'institutional_code' => $data['institutional_code'],
                'first_names' => $data['first_names'],
                'last_names' => $data['last_names'],
                'is_active' => true,
            ]);
            $this->syncEnrollment($student, $data['course_id'] ?? null);
            $this->audit->mustLog('estudiante_creado', AuditResult::Exito, $student);
        });

        return redirect()->route('admin.estudiantes.index')->with('success', 'Estudiante registrado.');
    }

    public function edit(Student $student): View
    {
        $current = AcademicPeriod::current();
        $courseId = $current ? $student->enrollments()->where('academic_period_id', $current->id)->value('course_id') : null;

        return view('admin.students.form', ['student' => $student, 'current' => $current, 'courses' => $this->currentCourses($current), 'courseId' => $courseId]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validated($request, $student);

        DB::transaction(function () use ($student, $data) {
            $student->update([
                'institutional_code' => $data['institutional_code'],
                'first_names' => $data['first_names'],
                'last_names' => $data['last_names'],
            ]);
            $this->syncEnrollment($student, $data['course_id'] ?? null);
            $this->audit->mustLog('estudiante_actualizado', AuditResult::Exito, $student);
        });

        return redirect()->route('admin.estudiantes.index')->with('success', 'Datos del estudiante actualizados.');
    }

    public function toggle(Student $student): RedirectResponse
    {
        $student->forceFill(['is_active' => ! $student->is_active])->save();
        $this->audit->success($student->is_active ? 'estudiante_activado' : 'estudiante_desactivado', $student);

        return back()->with('success', $student->is_active ? 'Estudiante activado.' : 'Estudiante desactivado.');
    }

    public function importForm(): View
    {
        $current = AcademicPeriod::current();

        return view('admin.students.import', ['current' => $current, 'courses' => $current ? $current->courses()->active()->orderBy('level')->orderBy('parallel')->get() : collect()]);
    }

    /**
     * Importa un CSV separado por punto y coma: codigo;nombres;apellidos;curso_id
     * Es atómico: si una fila es inválida no se guarda ninguna.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048']], [], ['archivo' => 'el archivo']);
        $current = AcademicPeriod::current();
        if (! $current) {
            return back()->with('error', 'Configure un periodo lectivo vigente antes de importar.');
        }
        $validCourses = $current->courses()->active()->pluck('id')->all();

        $handle = fopen($request->file('archivo')->getRealPath(), 'r');
        $rows = [];
        $errors = [];
        $line = 0;
        while (($cols = fgetcsv($handle, 1000, ';')) !== false) {
            $line++;
            $cols = array_map(fn ($c) => trim((string) $c), $cols);
            if ($line === 1 && isset($cols[0]) && mb_strtolower(preg_replace('/^\xEF\xBB\xBF/', '', $cols[0])) === 'codigo') {
                continue; // encabezado
            }
            if (count(array_filter($cols, fn ($c) => $c !== '')) === 0) {
                continue;
            }
            [$code, $first, $last, $course] = array_pad($cols, 4, '');
            if ($code === '' || $first === '' || $last === '' || mb_strlen($code) > 30 || mb_strlen($first) > 100 || mb_strlen($last) > 100) {
                $errors[] = "Fila {$line}: código, nombres y apellidos son obligatorios (máx. 30/100/100 caracteres).";

                continue;
            }
            if ($course !== '' && ! in_array((int) $course, $validCourses, true)) {
                $errors[] = "Fila {$line}: el curso {$course} no existe en el periodo vigente.";

                continue;
            }
            $rows[] = compact('code', 'first', 'last', 'course');
        }
        fclose($handle);

        if ($errors) {
            return back()->withErrors(['archivo' => implode(' ', array_slice($errors, 0, 10))]);
        }
        if (! $rows) {
            return back()->withErrors(['archivo' => 'El archivo no contiene filas para importar.']);
        }

        $count = DB::transaction(function () use ($rows) {
            foreach ($rows as $r) {
                $student = Student::updateOrCreate(['institutional_code' => $r['code']], ['first_names' => $r['first'], 'last_names' => $r['last']]);
                if ($r['course'] !== '') {
                    $this->syncEnrollment($student, (int) $r['course']);
                }
            }

            return count($rows);
        });

        $this->audit->success('estudiantes_importados', null, ['filas' => $count]);

        return redirect()->route('admin.estudiantes.index')->with('success', "Se importaron {$count} estudiantes.");
    }

    private function syncEnrollment(Student $student, ?int $courseId): void
    {
        $current = AcademicPeriod::current();
        if (! $current) {
            return;
        }
        if ($courseId) {
            Enrollment::updateOrCreate(
                ['student_id' => $student->id, 'academic_period_id' => $current->id],
                ['course_id' => $courseId],
            );
        }
    }

    private function currentCourses(?AcademicPeriod $current): array
    {
        if (! $current) {
            return [];
        }

        return Course::where('academic_period_id', $current->id)->active()->orderBy('level')->orderBy('parallel')
            ->get()->mapWithKeys(fn ($c) => [$c->id => $c->display_name])->all();
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $current = AcademicPeriod::current();
        $data = $request->validate([
            'institutional_code' => ['required', 'string', 'max:30', Rule::unique('students', 'institutional_code')->ignore($student?->id)],
            'first_names' => ['required', 'string', 'max:100'],
            'last_names' => ['required', 'string', 'max:100'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('academic_period_id', $current?->id ?? 0)],
        ], [], [
            'institutional_code' => 'el código institucional', 'first_names' => 'los nombres',
            'last_names' => 'los apellidos', 'course_id' => 'el curso',
        ]);

        if (! empty($data['course_id']) && ! $current) {
            throw ValidationException::withMessages(['course_id' => 'No hay un periodo lectivo vigente.']);
        }

        return array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
    }
}
