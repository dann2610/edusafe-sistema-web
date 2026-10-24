<?php

namespace App\Http\Controllers\Admin;

use App\Support\ScopedRule;
use App\Enums\AuditResult;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public const SHIFTS = ['matutina' => 'Matutina', 'vespertina' => 'Vespertina', 'nocturna' => 'Nocturna'];

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $periods = AcademicPeriod::orderByDesc('starts_on')->get();
        $periodId = (int) $request->query('periodo', AcademicPeriod::current()?->id ?? $periods->first()?->id);

        $courses = Course::with('tutor')
            ->withCount('enrollments')
            ->where('academic_period_id', $periodId)
            ->orderByDesc('is_active')->orderBy('level')->orderBy('parallel')
            ->get();

        return view('admin.courses.index', compact('periods', 'periodId', 'courses'));
    }

    public function create(Request $request): View
    {
        return view('admin.courses.form', $this->formData() + [
            'course' => null,
            'defaultPeriod' => (int) $request->query('periodo', AcademicPeriod::current()?->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $course = DB::transaction(function () use ($data) {
            $course = Course::create($data + ['is_active' => true]);
            $this->audit->mustLog('curso_creado', AuditResult::Exito, $course);

            return $course;
        });

        return redirect()->route('admin.cursos.index', ['periodo' => $course->academic_period_id])->with('success', 'Curso registrado.');
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.form', $this->formData() + ['course' => $course, 'defaultPeriod' => $course->academic_period_id]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request, $course);

        DB::transaction(function () use ($course, $data) {
            $course->update($data);
            $this->audit->mustLog('curso_actualizado', AuditResult::Exito, $course);
        });

        return redirect()->route('admin.cursos.index', ['periodo' => $course->academic_period_id])->with('success', 'Curso actualizado.');
    }

    public function toggle(Course $course): RedirectResponse
    {
        $course->forceFill(['is_active' => ! $course->is_active])->save();
        $this->audit->success($course->is_active ? 'curso_activado' : 'curso_desactivado', $course);

        return back()->with('success', $course->is_active ? 'Curso activado.' : 'Curso desactivado.');
    }

    private function formData(): array
    {
        return [
            'periods' => AcademicPeriod::active()->orderByDesc('starts_on')->pluck('name', 'id')->all(),
            'tutors' => User::active()->ofInstitution(auth()->user()->institution_id)->withRole(RoleSlug::Docente)->orderBy('name')->pluck('name', 'id')->all(),
            'shifts' => self::SHIFTS,
        ];
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'academic_period_id' => ['required', 'integer', ScopedRule::own('academic_periods')],
            'level' => ['required', 'string', 'max:60'],
            'parallel' => ['required', 'string', 'max:5'],
            'shift' => ['required', Rule::in(array_keys(self::SHIFTS))],
            'tutor_user_id' => ['nullable', 'integer', ScopedRule::own('users')->where('is_active', true)],
        ], [], [
            'academic_period_id' => 'el periodo lectivo', 'level' => 'el nivel', 'parallel' => 'el paralelo',
            'shift' => 'la jornada', 'tutor_user_id' => 'el tutor',
        ]);
        $data['parallel'] = mb_strtoupper(trim($data['parallel']));
        $data['level'] = trim($data['level']);

        $duplicate = Course::withoutInstitutionScope()->where('institution_id', auth()->user()->institution_id)->where('academic_period_id', $data['academic_period_id'])
            ->where('level', $data['level'])->where('parallel', $data['parallel'])->where('shift', $data['shift'])
            ->when($course, fn ($q) => $q->whereKeyNot($course->id))->exists();
        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages(['level' => 'Ya existe ese curso, paralelo y jornada en el periodo seleccionado.']);
        }

        return $data;
    }
}
