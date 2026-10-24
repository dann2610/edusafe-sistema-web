<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QrController extends Controller
{
    public function __construct(private readonly QrCodeService $qr) {}

    public function show(): View
    {
        $url = $this->qr->targetUrl();

        return view('admin.qr', [
            'url' => $url,
            'configured' => (string) config('seguridad_escolar.qr.url_publica'),
            'destination' => config('seguridad_escolar.qr.destino'),
            'warnings' => $this->qr->warnings(),
            'institution' => auth()->user()->institution,
            'svg' => $url ? $this->qr->svg($url) : null,
        ]);
    }

    public function download(string $format, AuditLogger $audit): Response
    {
        $url = $this->qr->targetUrl();
        abort_unless($url && in_array($format, ['png', 'svg'], true), 404);

        $audit->success('qr_descargado', null, ['formato' => $format, 'url' => $url]);

        return $format === 'svg'
            ? response($this->qr->svg($url, 1024), 200, ['Content-Type' => 'image/svg+xml', 'Content-Disposition' => 'attachment; filename="qr-institucional.svg"'])
            : response($this->qr->png($url), 200, ['Content-Type' => 'image/png', 'Content-Disposition' => 'attachment; filename="qr-institucional.png"']);
    }
}
