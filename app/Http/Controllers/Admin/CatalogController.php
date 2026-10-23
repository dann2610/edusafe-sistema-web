<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditResult;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\CatalogRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Administración genérica de catálogos simples (ver CatalogRegistry).
 * Protegido por el permiso catalogos.gestionar en las rutas.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, string $catalog): View
    {
        $def = CatalogRegistry::get($catalog);
        $search = trim((string) $request->query('q', ''));

        $query = $def['model']::query()
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
        ]);
    }

    public function create(string $catalog): View
    {
        $def = CatalogRegistry::get($catalog);

        return view('admin.catalogs.form', ['def' => $def, 'item' => null]);
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $def = CatalogRegistry::get($catalog);
        $data = $this->validated($request, $def);

        $item = DB::transaction(function () use ($def, $data) {
            $item = new $def['model'];
            $item->fill($data);
            $item->is_active = true;
            $item->save();
            $this->audit->mustLog('catalogo_creado', AuditResult::Exito, $item, ['catalogo' => $def['key']]);

            return $item;
        });

        return redirect()->route('admin.catalogos.index', $catalog)
            ->with('success', "«{$item->name}» se registró correctamente.");
    }

    public function edit(string $catalog, int $id): View
    {
        $def = CatalogRegistry::get($catalog);

        return view('admin.catalogs.form', ['def' => $def, 'item' => $def['model']::findOrFail($id)]);
    }

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        $def = CatalogRegistry::get($catalog);
        /** @var Model $item */
        $item = $def['model']::findOrFail($id);
        $data = $this->validated($request, $def, $item->id);

        DB::transaction(function () use ($item, $data, $def) {
            $item->fill($data);
            $changes = array_keys($item->getDirty());
            $item->save();
            if ($changes) {
                $this->audit->mustLog('catalogo_actualizado', AuditResult::Exito, $item, ['catalogo' => $def['key'], 'campos' => $changes]);
            }
        });

        return redirect()->route('admin.catalogos.index', $catalog)->with('success', 'Cambios guardados correctamente.');
    }

    public function toggle(string $catalog, int $id): RedirectResponse
    {
        $def = CatalogRegistry::get($catalog);
        $item = $def['model']::findOrFail($id);

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
        // Las casillas no marcadas no se envían: se normalizan a false
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

        return validator($input, CatalogRegistry::rules($def, $ignoreId), [], $attributes)->validate();
    }
}
