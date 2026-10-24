<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\AuditLogger;
use App\Services\ModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Configuración local de la propia institución (administrador institucional):
 * contacto, opciones del formulario público, requisitos de cierre y módulos
 * autorizados por la plataforma.
 */
class InstitutionSettingsController extends Controller
{
    public function show(Request $request, ModuleService $modules): View
    {
        $institution = $request->user()->institution;

        return view('admin.institution', [
            'institution' => $institution,
            'modules' => $modules->overview($institution)->filter(fn ($m) => $m->authorized || ! $m->module->is_implemented),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $institution = $request->user()->institution;
        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +()\-]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'public_report_hourly_limit' => ['required', 'integer', 'between:1,100'],
        ], [], ['address' => 'la dirección', 'phone' => 'el teléfono', 'email' => 'el correo', 'public_report_hourly_limit' => 'el límite de reportes por hora']);

        $data['allow_reports_without_contact'] = $request->boolean('allow_reports_without_contact');
        $data['close_requires_followup'] = $request->boolean('close_requires_followup');
        $data['close_requires_measures_resolved'] = $request->boolean('close_requires_measures_resolved');

        DB::transaction(function () use ($institution, $data, $audit) {
            $institution->fill($data);
            $changes = array_keys($institution->getDirty());
            $institution->save();
            if ($changes) {
                $audit->mustLog('configuracion_institucional', \App\Enums\AuditResult::Exito, $institution, ['campos' => $changes]);
            }
        });

        return back()->with('success', 'Configuración guardada.');
    }

    public function toggleModule(Request $request, Module $module, ModuleService $modules): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        $modules->setEnabled($request->user()->institution, $module, $enabled, $request->user());

        return back()->withFragment('modulos')->with('success', $enabled ? "Módulo «{$module->name}» activado." : "Módulo «{$module->name}» desactivado.");
    }
}
