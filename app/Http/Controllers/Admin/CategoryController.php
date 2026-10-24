<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditResult;
use App\Enums\CasePriority;
use App\Enums\FieldType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IncidentCategory;
use App\Models\IncidentCategoryField;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Categorías de incidentes.
 * - Superadministrador: categorías GLOBALES (institution_id NULL), visibles en todas las instituciones.
 * - Administrador institucional: categorías LOCALES de su institución; las globales son de solo lectura.
 * No se eliminan: se desactivan. Cada cambio queda en la bitácora con valores anterior y nuevo.
 */
class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    private function isPlatform(Request $request): bool
    {
        return $request->user()->isSuperadmin();
    }

    private function routePrefix(Request $request): string
    {
        return $this->isPlatform($request) ? 'platform.categories' : 'admin.categories';
    }

    /** El usuario solo modifica categorías de su ámbito. */
    private function ensureEditable(Request $request, IncidentCategory $category): void
    {
        $ok = $this->isPlatform($request) ? $category->isGlobal() : $category->isOwnedBy($request->user()->institution_id);
        abort_unless($ok, 403);
    }

    private function find(Request $request, int $id): IncidentCategory
    {
        $query = $this->isPlatform($request) ? IncidentCategory::withoutInstitutionScope()->whereNull('institution_id') : IncidentCategory::query();

        return $query->findOrFail($id);
    }

    public function index(Request $request): View
    {
        $query = $this->isPlatform($request) ? IncidentCategory::withoutInstitutionScope()->whereNull('institution_id') : IncidentCategory::query();

        return view('admin.categories.index', [
            'categories' => $query->withCount(['fields', 'incidents'])->with('defaultAssignee')->orderByDesc('is_active')->ordered()->get(),
            'platform' => $this->isPlatform($request),
            'prefix' => $this->routePrefix($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.categories.form', $this->formData($request) + ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $category = DB::transaction(function () use ($request, $data) {
            $category = new IncidentCategory($data);
            $category->default_assignee_user_id = $this->isPlatform($request) ? null : ($data['default_assignee_user_id'] ?? null);
            $category->institution_id = $this->isPlatform($request) ? null : $request->user()->institution_id;
            $category->creatingGlobal = $this->isPlatform($request);
            $category->is_active = true;
            $category->save();
            $this->audit->mustLog('categoria_creada', AuditResult::Exito, $category, ['codigo' => $category->code, 'ambito' => $category->isGlobal() ? 'global' : 'local']);

            return $category;
        });

        return redirect()->route($this->routePrefix($request).'.edit', $category->id)
            ->with('success', "Categoría «{$category->name}» creada. Ahora puede agregar campos adicionales.");
    }

    public function edit(Request $request, int $id): View
    {
        $category = $this->find($request, $id);
        $editable = $this->isPlatform($request) ? $category->isGlobal() : $category->isOwnedBy($request->user()->institution_id);

        $history = AuditLog::withoutInstitutionScope()
            ->where('auditable_type', 'IncidentCategory')->where('auditable_id', $category->id)
            ->with('user')->latest('created_at')->latest('id')->limit(20)->get();

        return view('admin.categories.form', $this->formData($request) + [
            'category' => $category->load('fields'),
            'editable' => $editable,
            'history' => $history,
            'fieldTypes' => FieldType::options(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = $this->find($request, $id);
        $this->ensureEditable($request, $category);
        $data = $this->validated($request, $category);

        DB::transaction(function () use ($request, $category, $data) {
            $before = $category->only(['name', 'description', 'default_priority', 'available_in_public_form', 'sort_order', 'default_assignee_user_id']);
            $category->fill($data);
            if (! $this->isPlatform($request)) {
                $category->default_assignee_user_id = $data['default_assignee_user_id'] ?? null;
            }
            $dirty = $category->getDirty();
            $category->save();
            if ($dirty) {
                $this->audit->mustLog('categoria_actualizada', AuditResult::Exito, $category, ['cambios' => $this->diff($before, $dirty)]);
            }
        });

        return back()->with('success', 'Categoría actualizada. Los reportes anteriores conservan sus datos.');
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $category = $this->find($request, $id);
        $this->ensureEditable($request, $category);

        $category->forceFill(['is_active' => ! $category->is_active])->save();
        $this->audit->success($category->is_active ? 'categoria_activada' : 'categoria_desactivada', $category);

        return back()->with('success', $category->is_active ? 'Categoría activada.' : 'Categoría desactivada: deja de aparecer en los formularios y se conservan sus reportes.');
    }

    // ------------------------------------------------------------ campos

    public function storeField(Request $request, int $id): RedirectResponse
    {
        $category = $this->find($request, $id);
        $this->ensureEditable($request, $category);
        $data = $this->validatedField($request, $category);

        DB::transaction(function () use ($category, $data) {
            $field = $category->fields()->create($data + ['is_active' => true]);
            $this->audit->mustLog('categoria_campo_creado', AuditResult::Exito, $category, ['campo' => $field->key, 'tipo' => $field->type->value]);
        });

        return back()->withFragment('campos')->with('success', 'Campo adicional agregado.');
    }

    public function updateField(Request $request, int $id, IncidentCategoryField $field): RedirectResponse
    {
        $category = $this->find($request, $id);
        $this->ensureEditable($request, $category);
        abort_unless($field->incident_category_id === $category->id, 404);
        $data = $this->validatedField($request, $category, $field);

        DB::transaction(function () use ($category, $field, $data) {
            $field->fill($data);
            $dirty = array_keys($field->getDirty());
            $field->save();
            if ($dirty) {
                $this->audit->mustLog('categoria_campo_actualizado', AuditResult::Exito, $category, ['campo' => $field->key, 'campos' => $dirty]);
            }
        });

        return back()->withFragment('campos')->with('success', 'Campo actualizado.');
    }

    public function toggleField(Request $request, int $id, IncidentCategoryField $field): RedirectResponse
    {
        $category = $this->find($request, $id);
        $this->ensureEditable($request, $category);
        abort_unless($field->incident_category_id === $category->id, 404);

        $field->forceFill(['is_active' => ! $field->is_active])->save();
        $this->audit->success($field->is_active ? 'categoria_campo_activado' : 'categoria_campo_desactivado', $category, ['campo' => $field->key]);

        return back()->withFragment('campos')->with('success', $field->is_active ? 'Campo activado.' : 'Campo desactivado. Los reportes anteriores conservan el dato registrado.');
    }

    // ------------------------------------------------------------ apoyo

    private function formData(Request $request): array
    {
        return [
            'platform' => $this->isPlatform($request),
            'prefix' => $this->routePrefix($request),
            'priorities' => CasePriority::options(),
            'assignees' => $this->isPlatform($request) ? [] : User::active()->ofInstitution($request->user()->institution_id)
                ->whereHas('role.permissions', fn ($q) => $q->where('slug', 'seguimientos.registrar'))
                ->orderBy('name')->pluck('name', 'id')->all(),
            'editable' => true,
            'history' => collect(),
            'fieldTypes' => FieldType::options(),
        ];
    }

    private function validated(Request $request, ?IncidentCategory $category = null): array
    {
        $institutionId = $this->isPlatform($request) ? null : $request->user()->institution_id;
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'available_in_public_form' => $request->boolean('available_in_public_form'),
        ]);
        // El código y el nombre no pueden repetir los de una categoría global ni los de la propia institución
        $unique = fn (string $col) => function (string $attr, $value, $fail) use ($col, $institutionId, $category) {
            $exists = IncidentCategory::withoutInstitutionScope()
                ->where(fn ($q) => $q->whereNull('institution_id')->when($institutionId, fn ($w) => $w->orWhere('institution_id', $institutionId)))
                ->whereRaw("LOWER({$col}) = ?", [mb_strtolower((string) $value)])
                ->when($category, fn ($q) => $q->whereKeyNot($category->id))->exists();
            if ($exists) {
                $fail('Ya existe una categoría con ese valor (global o de su institución).');
            }
        };

        return $request->validate([
            'code' => $category ? ['prohibited_unless:code,'.$category->code] : ['required', 'string', 'regex:/^[A-Z0-9_]{2,30}$/', $unique('code')],
            'name' => ['required', 'string', 'max:120', $unique('name')],
            'description' => ['nullable', 'string', 'max:500'],
            'default_priority' => ['required', Rule::enum(CasePriority::class)],
            'available_in_public_form' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'default_assignee_user_id' => $this->isPlatform($request) ? ['prohibited'] : [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('institution_id', $institutionId)->where('is_active', true),
            ],
        ], [
            'code.regex' => 'Use de 2 a 30 mayúsculas, números o guion bajo. Ejemplo: CIBERACOSO.',
            'code.prohibited_unless' => 'El código de una categoría no puede cambiarse.',
        ], [
            'code' => 'el código', 'name' => 'el nombre', 'description' => 'la descripción',
            'default_priority' => 'la prioridad inicial', 'sort_order' => 'el orden', 'default_assignee_user_id' => 'el responsable inicial',
        ]) + ['sort_order' => (int) $request->input('sort_order', 100)];
    }

    private function validatedField(Request $request, IncidentCategory $category, ?IncidentCategoryField $field = null): array
    {
        $options = collect(preg_split('/\R/', (string) $request->input('options_text', '')))
            ->map(fn ($o) => trim(str_replace(',', ' ', $o)))->filter()->unique()->values()->all();
        $request->merge([
            'key' => $field ? $field->key : mb_strtolower(trim((string) $request->input('key'))),
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'show_in_public_form' => $request->boolean('show_in_public_form'),
        ]);

        $data = $request->validate([
            'key' => $field ? [] : ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,39}$/', Rule::unique('incident_category_fields', 'key')->where('incident_category_id', $category->id)],
            'label' => ['required', 'string', 'max:120'],
            'type' => $field ? ['prohibited_unless:type,'.$field->type->value] : ['required', Rule::enum(FieldType::class)],
            'options' => [Rule::requiredIf(fn () => ($field?->type->value ?? $request->input('type')) === 'lista'), 'array', 'max:20'],
            'options.*' => ['string', 'max:80'],
            'is_required' => ['boolean'],
            'show_in_public_form' => ['boolean'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ], [
            'key.regex' => 'El identificador debe empezar con una letra y usar minúsculas, números o guion bajo.',
            'key.unique' => 'Ya existe un campo con ese identificador en la categoría.',
            'options.required' => 'Escriba al menos dos opciones, una por línea.',
            'type.prohibited_unless' => 'El tipo de un campo no puede cambiarse; desactívelo y cree uno nuevo.',
        ], ['key' => 'el identificador', 'label' => 'la etiqueta', 'type' => 'el tipo', 'help_text' => 'el texto de ayuda']);

        $type = $field?->type->value ?? $data['type'];
        if ($type === 'lista' && count($options) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages(['options_text' => 'Una lista necesita al menos dos opciones.']);
        }
        $data['options'] = $type === 'lista' ? $options : null;
        $data['sort_order'] = (int) $request->input('sort_order', 0);
        if ($field) {
            unset($data['key'], $data['type']);
        }

        return $data;
    }

    /** Diferencias legibles para el historial (sin datos sensibles). */
    private function diff(array $before, array $dirty): array
    {
        $labels = ['name' => 'nombre', 'description' => 'descripcion_publica', 'default_priority' => 'prioridad', 'available_in_public_form' => 'formulario_publico', 'sort_order' => 'orden', 'default_assignee_user_id' => 'responsable_inicial', 'is_active' => 'activa'];
        $out = [];
        foreach ($dirty as $col => $new) {
            $old = $before[$col] ?? null;
            $old = $old instanceof \BackedEnum ? $old->value : $old;
            $out[$labels[$col] ?? $col] = ['antes' => is_bool($old) ? ($old ? 'sí' : 'no') : (string) $old, 'despues' => is_bool($new) ? ($new ? 'sí' : 'no') : (string) $new];
        }

        return $out;
    }
}
