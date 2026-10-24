<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\ModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Catálogo global de módulos. */
class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = Module::orderBy('sort_order')->withCount(['institutions as enabled_count' => fn ($q) => $q->where('institution_module.is_enabled', true)])->get();

        return view('platform.modules', [
            'modules' => $modules,
            'names' => $modules->pluck('name', 'key'),
        ]);
    }

    public function toggle(Request $request, Module $module, ModuleService $service): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        $service->setGlobal($module, $enabled, $request->user());

        return back()->with('success', $enabled ? "«{$module->name}» habilitado en la plataforma." : "«{$module->name}» deshabilitado para todas las instituciones.");
    }
}
