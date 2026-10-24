<?php

namespace App\Http\Controllers\Platform;

use App\Enums\InstitutionStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\InstitutionRequest;
use App\Models\Incident;
use App\Models\Institution;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\InstitutionService;
use App\Services\ModuleService;
use App\Services\UserAccountService;
use App\Support\Ecuador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de instituciones (superadministrador). Solo datos administrativos y
 * agregados: el superadministrador no accede a expedientes de ninguna institución.
 */
class InstitutionController extends Controller
{
    public function __construct(private readonly InstitutionService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(InstitutionStatus::class)],
            'provincia' => ['nullable', Rule::in(Ecuador::PROVINCES)],
        ]);

        $institutions = Institution::query()
            ->withCount('users')
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term)).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(code_prefix) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(amie_code, \'\')) LIKE ?', [$like]));
            })
            ->when($filters['estado'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['provincia'] ?? null, fn ($q, $v) => $q->where('province', $v))
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        $openCases = Incident::withoutInstitutionScope()->open()->whereIn('institution_id', $institutions->pluck('id'))
            ->selectRaw('institution_id, COUNT(*) AS total')->groupBy('institution_id')->pluck('total', 'institution_id');

        return view('platform.institutions.index', [
            'institutions' => $institutions,
            'openCases' => $openCases,
            'filters' => $filters,
            'provinces' => Ecuador::PROVINCES,
        ]);
    }

    public function create(): View
    {
        return view('platform.institutions.form', ['institution' => null, 'provinces' => Ecuador::PROVINCES]);
    }

    public function store(InstitutionRequest $request): RedirectResponse
    {
        [$institution, $admin, $password] = $this->service->create($request->validated(), $request->user());

        return redirect()->route('platform.institutions.show', $institution)
            ->with('success', "Institución «{$institution->name}» registrada con su administrador inicial.")
            ->with('temporary_password', $password)
            ->with('temporary_user', $admin->email);
    }

    public function show(Institution $institution, ModuleService $modules): View
    {
        $counts = Incident::withoutInstitutionScope()->where('institution_id', $institution->id)->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(*) FILTER (WHERE status <> 'cerrado') AS abiertos")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'cerrado') AS cerrados")
            ->selectRaw("COUNT(*) FILTER (WHERE source = 'publico') AS publicos")
            ->first();

        return view('platform.institutions.show', [
            'institution' => $institution,
            'admins' => User::ofInstitution($institution->id)->withRole(RoleSlug::Administrador)->orderBy('name')->get(),
            'usersByRole' => User::ofInstitution($institution->id)->join('roles', 'roles.id', '=', 'users.role_id')
                ->selectRaw('roles.name AS rol, COUNT(*) AS total')->groupBy('roles.name')->pluck('total', 'rol'),
            'counts' => $counts,
            'modules' => $modules->overview($institution),
            'statuses' => InstitutionStatus::cases(),
        ]);
    }

    public function edit(Institution $institution): View
    {
        return view('platform.institutions.form', ['institution' => $institution, 'provinces' => Ecuador::PROVINCES]);
    }

    public function update(InstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $this->service->update($institution, $request->validated(), $request->user());

        return redirect()->route('platform.institutions.show', $institution)->with('success', 'Datos de la institución actualizados.');
    }

    public function status(Request $request, Institution $institution): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(InstitutionStatus::class)],
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ], [], ['status' => 'el estado', 'reason' => 'el motivo']);

        $status = InstitutionStatus::from($data['status']);
        $this->service->changeStatus($institution, $status, $data['reason'], $request->user());

        return back()->with('success', "La institución quedó en estado «{$status->label()}». Sus datos históricos se conservan.");
    }

    public function addAdmin(Request $request, Institution $institution, UserAccountService $accounts): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
        ], [], ['name' => 'el nombre', 'email' => 'el correo']);

        [$admin, $password] = $accounts->create($data + [
            'position' => 'Administrador institucional',
            'role_id' => Role::where('slug', RoleSlug::Administrador->value)->value('id'),
        ], $institution->id);

        return back()->with('success', 'Administrador institucional creado.')
            ->with('temporary_password', $password)->with('temporary_user', $admin->email);
    }

    public function authorizeModule(Request $request, Institution $institution, Module $module, ModuleService $modules): RedirectResponse
    {
        $authorized = $request->boolean('authorized');
        $modules->setAuthorized($institution, $module, $authorized, $request->user());

        return back()->with('success', $authorized ? "Módulo «{$module->name}» autorizado." : "Módulo «{$module->name}» retirado.");
    }
}
