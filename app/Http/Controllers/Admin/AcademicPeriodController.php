<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditResult;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicPeriodController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.periods.index', [
            'periods' => AcademicPeriod::withCount(['courses', 'incidents'])->orderByDesc('starts_on')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.periods.form', ['period' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $period = AcademicPeriod::create($data + ['is_active' => true]);
            if (! AcademicPeriod::current()) {
                $period->markAsCurrent();
            }
            $this->audit->mustLog('periodo_creado', AuditResult::Exito, $period);
        });

        return redirect()->route('admin.periodos.index')->with('success', 'Periodo lectivo registrado.');
    }

    public function edit(AcademicPeriod $period): View
    {
        return view('admin.periods.form', compact('period'));
    }

    public function update(Request $request, AcademicPeriod $period): RedirectResponse
    {
        $data = $this->validated($request, $period);

        DB::transaction(function () use ($period, $data) {
            $period->update($data);
            $this->audit->mustLog('periodo_actualizado', AuditResult::Exito, $period);
        });

        return redirect()->route('admin.periodos.index')->with('success', 'Periodo lectivo actualizado.');
    }

    public function makeCurrent(AcademicPeriod $period): RedirectResponse
    {
        if (! $period->is_active) {
            return back()->with('error', 'Active el periodo antes de marcarlo como vigente.');
        }

        $period->markAsCurrent();
        $this->audit->success('periodo_vigente', $period);

        return back()->with('success', "«{$period->name}» es ahora el periodo vigente.");
    }

    public function toggle(AcademicPeriod $period): RedirectResponse
    {
        if ($period->is_current && $period->is_active) {
            return back()->with('error', 'No se puede desactivar el periodo vigente. Marque otro como vigente primero.');
        }

        $period->forceFill(['is_active' => ! $period->is_active])->save();
        $this->audit->success($period->is_active ? 'periodo_activado' : 'periodo_desactivado', $period);

        return back()->with('success', $period->is_active ? 'Periodo activado.' : 'Periodo desactivado.');
    }

    private function validated(Request $request, ?AcademicPeriod $period = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('academic_periods', 'name')->ignore($period?->id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ], [], ['name' => 'el nombre', 'starts_on' => 'la fecha de inicio', 'ends_on' => 'la fecha de finalización']);
    }
}
