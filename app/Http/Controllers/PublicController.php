<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\ReporterRelationship;
use App\Http\Requests\PublicReportRequest;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Institution;
use App\Services\IncidentRegistrar;
use App\Services\ModuleService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Páginas públicas por institución (destino del código QR).
 * La institución la resuelve ResolvePublicInstitution. Nunca muestran expedientes.
 */
class PublicController extends Controller
{
    public function __construct(private readonly ModuleService $modules) {}

    private function institution(): Institution
    {
        return app(TenantContext::class)->institution();
    }

    public function home(): View
    {
        return view('public.home', [
            'institution' => $this->institution(),
            'publicReports' => $this->modules->enabled('reporte_publico'),
            'statusLookup' => $this->modules->enabled('consulta_estado'),
            'otherInstitutions' => Institution::publiclyAvailable()->count() > 1,
        ]);
    }

    public function directory(): View
    {
        return view('public.directory', ['institutions' => Institution::publiclyAvailable()->orderBy('name')->get()]);
    }

    public function privacy(): View
    {
        return view('public.privacy', ['institution' => $this->institution()]);
    }

    public function reportForm(): View
    {
        $institution = $this->institution();
        $categories = IncidentCategory::forPublic()
            ->with(['activeFields' => fn ($q) => $q->where('show_in_public_form', true)])
            ->get();

        return view('public.report', [
            'institution' => $institution,
            'categories' => $categories,
            'relationships' => ReporterRelationship::options(),
            'token' => PublicReportRequest::token(),
            'allowAnonymous' => $institution->allow_reports_without_contact,
            'allowEvidence' => $this->modules->enabled('evidencias_publicas'),
            'maxMb' => round(config('seguridad_escolar.adjuntos.max_kb') / 1024, 1),
        ]);
    }

    public function reportStore(PublicReportRequest $request, IncidentRegistrar $registrar): RedirectResponse
    {
        $institution = $this->institution();
        [$incident, $key] = $registrar->registerPublic(
            $institution,
            $request->incidentData(),
            $request->participants(),
            $this->modules->enabled('evidencias_publicas') ? $request->file('attachments', []) : [],
            $this->modules->enabled('consulta_estado'),
        );

        // La confirmación se muestra solo si el registro realmente se guardó
        return redirect()->to($institution->publicRoute('report.sent'))->with('report', [
            'code' => $incident->code,
            'received_at' => $incident->created_at->format('d/m/Y H:i'),
            'urgent' => $incident->immediate_risk,
            'key' => $key,
        ]);
    }

    public function reportSent(): View|RedirectResponse
    {
        $report = session('report');
        if (! $report) {
            return redirect()->to($this->institution()->publicRoute('home'));
        }

        return view('public.report-sent', ['report' => $report, 'institution' => $this->institution()]);
    }

    public function statusForm(): View
    {
        return view('public.status', ['institution' => $this->institution(), 'result' => null]);
    }

    /**
     * Consulta de estado con código + clave. Respuesta genérica si no coincide,
     * para no revelar qué códigos existen. Solo muestra estado y fechas.
     */
    public function statusLookup(Request $request): View
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'key' => ['required', 'string', 'max:20'],
        ], [], ['code' => 'el código', 'key' => 'la clave']);

        $incident = Incident::where('code', strtoupper(trim($data['code'])))->first();
        $valid = $incident && $incident->public_access_hash && Hash::check(strtoupper(trim($data['key'])), $incident->public_access_hash);

        if (! $valid) {
            return view('public.status', [
                'institution' => $this->institution(),
                'result' => null,
                'notFound' => true,
            ]);
        }

        $lastChange = $incident->statusHistory()->latest('created_at')->value('created_at');

        return view('public.status', [
            'institution' => $this->institution(),
            'result' => [
                'code' => $incident->code,
                'status' => $incident->status,
                'received_at' => $incident->created_at,
                'updated_at' => $lastChange ? \Carbon\Carbon::parse($lastChange) : $incident->created_at,
                'message' => $this->statusMessage($incident->status),
            ],
        ]);
    }

    private function statusMessage(CaseStatus $status): string
    {
        return match ($status) {
            CaseStatus::Registrado => 'Su reporte fue recibido y está en espera de revisión por el DECE.',
            CaseStatus::EnRevision => 'El DECE está revisando la información.',
            CaseStatus::RequiereInformacion => 'El DECE necesita más información. Si dejó un contacto, se comunicarán con usted; también puede acercarse a la institución con su código.',
            CaseStatus::Asignado, CaseStatus::EnSeguimiento => 'El caso está siendo atendido por la institución.',
            CaseStatus::Derivado => 'El caso fue derivado a una entidad competente conforme a los protocolos.',
            CaseStatus::Cerrado => 'La institución concluyó la atención de este caso.',
        };
    }
}
