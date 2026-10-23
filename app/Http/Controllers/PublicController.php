<?php

namespace App\Http\Controllers;

use App\Enums\ReporterRelationship;
use App\Http\Requests\PublicReportRequest;
use App\Models\IncidentCategory;
use App\Services\IncidentRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Páginas públicas: destino del código QR institucional.
 * No muestran expedientes ni datos de casos.
 */
class PublicController extends Controller
{
    public function home(): View
    {
        return view('public.home', ['publicReports' => config('seguridad_escolar.reportes_publicos.habilitados')]);
    }

    public function privacy(): View
    {
        return view('public.privacy');
    }

    public function reportForm(): View
    {
        abort_unless(config('seguridad_escolar.reportes_publicos.habilitados'), 404);

        return view('public.report', [
            'categories' => IncidentCategory::active()->where('available_in_public_form', true)->ordered()->get(),
            'relationships' => ReporterRelationship::options(),
            'token' => PublicReportRequest::token(),
        ]);
    }

    public function reportStore(PublicReportRequest $request, IncidentRegistrar $registrar): RedirectResponse
    {
        $incident = $registrar->registerPublic($request->safe()->except(['privacy', 'sitio_web', 'form_token']));

        return redirect()->route('public.report.sent')->with('report_code', $incident->code)->with('report_urgent', $incident->immediate_risk);
    }

    public function reportSent(): View|RedirectResponse
    {
        if (! session()->has('report_code')) {
            return redirect()->route('public.home');
        }

        return view('public.report-sent', ['code' => session('report_code'), 'urgent' => session('report_urgent')]);
    }
}
