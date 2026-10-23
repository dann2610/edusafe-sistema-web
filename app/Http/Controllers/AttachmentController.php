<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga de evidencias: siempre con autorización y registro en la bitácora.
 * Los archivos viven en storage/app/private y no tienen URL pública.
 */
class AttachmentController extends Controller
{
    public function download(Attachment $attachment, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('download', $attachment);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->stored_path), 404);

        $audit->success('evidencia_descargada', $attachment->incident, ['adjunto_id' => $attachment->id]);

        return $disk->download($attachment->stored_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
