<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExternalEntityType;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\AuditLog;
use App\Models\ExternalEntity;
use App\Models\Role;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserAccountService $accounts) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'rol' => ['nullable', 'string', 'in:'.implode(',', RoleSlug::values())],
            'estado' => ['nullable', 'in:activos,inactivos'],
        ]);

        $users = User::query()
            ->with(['role', 'externalEntity'])
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($term)).'%';
                $q->where(fn (Builder $w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhere('email', 'like', $like));
            })
            ->when($filters['rol'] ?? null, fn (Builder $q, string $rol) => $q->withRole(RoleSlug::from($rol)))
            ->when(($filters['estado'] ?? null) === 'activos', fn (Builder $q) => $q->where('is_active', true))
            ->when(($filters['estado'] ?? null) === 'inactivos', fn (Builder $q) => $q->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => RoleSlug::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', $this->formData());
    }

    public function store(UserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        [$user, $temporary] = $this->accounts->create($request->validated());

        return redirect()->route('admin.usuarios.show', $user)
            ->with('success', 'Cuenta creada correctamente.')
            ->with('temporary_password', $temporary);
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $user->load('role', 'externalEntity');
        $activity = AuditLog::where(fn (Builder $q) => $q->where('user_id', $user->id)
            ->orWhere(fn (Builder $w) => $w->where('auditable_type', 'User')->where('auditable_id', $user->id)))
            ->latest('created_at')->latest('id')->limit(10)->get();

        return view('admin.users.show', compact('user', 'activity'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', $this->formData() + [
            'user' => $user->load('role'),
            'canChangeRole' => Gate::allows('changeRole', $user),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->accounts->update($user, $request->validated(), Gate::allows('changeRole', $user));

        return redirect()->route('admin.usuarios.show', $user)->with('success', 'Cuenta actualizada correctamente.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:200']], [], ['reason' => 'el motivo']);
        $this->accounts->deactivate($user, $data['reason']);

        return redirect()->route('admin.usuarios.show', $user)->with('success', 'La cuenta fue desactivada y sus sesiones cerradas.');
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('activate', $user);

        $this->accounts->activate($user);

        return redirect()->route('admin.usuarios.show', $user)->with('success', 'La cuenta fue activada.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $temporary = $this->accounts->resetTemporaryPassword($user);

        return redirect()->route('admin.usuarios.show', $user)
            ->with('success', 'Se generó una nueva contraseña temporal. La persona deberá cambiarla al ingresar.')
            ->with('temporary_password', $temporary);
    }

    private function formData(): array
    {
        return [
            'roles' => Role::orderBy('id')->get(),
            'entities' => ExternalEntity::active()->whereIn('type', ExternalEntityType::policeTypes())->orderBy('name')->get(),
            'policeRoleId' => Role::where('slug', RoleSlug::Policia->value)->value('id'),
        ];
    }
}
