<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditResult;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\CatalogRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Catálogos simples (entidades externas, tipos de medida, tipos de actuación).
 * - Superadministrador: administra los registros GLOBALES.
 * - Administrador institucional: administra los LOCALES; los globales son de solo lectura.
 * Los registros no se eliminan: se desactivan.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    private function platform(Request $request): bool
    {
        return $request->user()->isSuperadmin();
    }

    private function prefix(Request $request): string
    {
        return $this->platform($request) ? 'platform.catalogos' : 'admin.catalogos';
    }

    private function query(Request $request, array $def): Builder
    {
        return $this->platform($request)
            ? $def['model']::withoutInstitutionScope()->whereNull('institution_id')
            : $def['model']::query();
    }

    private function editable(Request $request, Model $item): bool
    {
        return $this->platform($request) ? $item->isGlobal() : $item->isOwnedBy($request->user()->institution_id);
    }

    public function index(Request $request, string $catalog): View
    {
        $def = CatalogRegistry::get($catalog);
        $search = trim((string) $request->query('q', ''));

        $query = $this->query($request, $def)
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search)).'%';
                $q->whereRaw('LOWER(name) LIKE ?', [$like]);
            })
            ->orderByDesc('is_active');
        foreach ($def['order'] as [$col, $dir]) {
            $query->orderBy($col, $dir);
        }

        return view('admin.catalogs.index', [
            'def' => $def,
            'items' => $query->paginate(20)->withQueryString(),
            'search' => $search,
            'prefix' => $this->prefix($request),
            'platform' => $this->platform($request),
            'institutionId' => $request->user()->institution_id,
        ]);
    }

    public function create(Request $request, string $catalog): View
    {
        return view('admin.catalogs.form', ['def' => CatalogRegistry::get($catalog), 'item' => null, 'prefix' => $this->prefix($request)]);
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $def = CatalogRegistry::get($catalog);
        $data = $this->validated($request, $def);

        $item = DB::transaction(function () use ($request, $def, $data) {
            $item = new $def['model'];
            $item->fill($data);
            $item->institution_id = $this->platform($request) ? null : $request->user()->institution_id;
            $item->creatingGlobal = $this->platform($request);
            $item->is_active = true;
            $item->save();
            $this->audit->mustLog('catalogo_creado', AuditResult::Exito, $item, ['catalogo' => $def['key'], 'ambito' => $item->isGlobal() ? 'global' : 'local']);

            return $item;
        });

        return redirect()->route($this->prefix($request).'.index', $catalog)->with('success', "«{$item->name}» se registró correctamente.");
    }

    public function edit(Request $request, string $catalog, int $id): View
    {
        $def = CatalogRegistry::get($catalog);
        $item = $this->query($request, $def)->findOrFail($id);
        abort_unless($this->editable($request, $item), 403);

        return view('admin.catalogs.form', ['def' => $def, 'item' => $item, 'prefix' => $this->prefix($request)]);
    }

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        $def = CatalogRegistry::get($catalog);
        /** @var Model $item */
        $item = $this->query($request, $def)->findOrFail($id);
        abort_unless($this->editable($request, $item), 403);
        $data = $this->validated($request, $def, $item->id);

        DB::transaction(function () use ($item, $data, $def) {
            $item->fill($data);
            $changes = array_keys($item->getDirty());
            $item->save();
            if ($changes) {
                $this->audit->mustLog('catalogo_actualizado', AuditResult::Exito, $item, ['catalogo' => $def['key'], 'campos' => $changes]);
            }
        });

        return redirect()->route($this->prefix($request).'.index', $catalog)->with('success', 'Cambios guardados correctamente.');
    }

    public function toggle(Request $request, string $catalog, int $id): RedirectResponse
    {
        $def = CatalogRegistry::get($catalog);
        $item = $this->query($request, $def)->findOrFail($id);
        abort_unless($this->editable($request, $item), 403);

        DB::transaction(function () use ($item, $def) {
            $item->is_active = ! $item->is_active;
            $item->save();
            $this->audit->mustLog($item->is_active ? 'catalogo_activado' : 'catalogo_desactivado', AuditResult::Exito, $item, ['catalogo' => $def['key']]);
        });

        return back()->with('success', $item->is_active
            ? "«{$item->name}» fue activado."
            : "«{$item->name}» fue desactivado. Los casos que lo usan conservan su historial.");
    }

    private function validated(Request $request, array $def, ?int $ignoreId = null): array
    {
        $input = $request->all();
        foreach ($def['fields'] as $name => $f) {
            if ($f['type'] === 'checkbox') {
                $input[$name] = $request->boolean($name);
            }
            if (isset($input[$name]) && is_string($input[$name])) {
                $input[$name] = trim($input[$name]) === '' ? null : trim($input[$name]);
            }
        }
        $attributes = collect($def['fields'])->mapWithKeys(fn ($f, $k) => [$k => mb_strtolower($f['label'])])->all();
        $institutionId = $this->platform($request) ? null : $request->user()->institution_id;

        return validator($input, CatalogRegistry::rules($def, $ignoreId, $institutionId), [], $attributes)->validate();
    }
}
