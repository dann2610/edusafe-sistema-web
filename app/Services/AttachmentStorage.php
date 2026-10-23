<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Almacena evidencias en el disco privado (storage/app/private), fuera de public/.
 * - Nombre aleatorio: el nombre original solo se guarda como dato.
 * - Se verifica el tipo real del contenido (finfo), no solo la extensión.
 * - Se calcula SHA-256 para control de integridad.
 * La descarga siempre pasa por AttachmentController con autorización.
 */
class AttachmentStorage
{
    public static function validationRules(): array
    {
        $cfg = config('seguridad_escolar.adjuntos');

        return [
            'attachments' => ['nullable', 'array', 'max:'.$cfg['max_archivos']],
            'attachments.*' => ['file', 'mimes:'.implode(',', $cfg['extensiones']), 'max:'.$cfg['max_kb']],
        ];
    }

    public function store(UploadedFile $file, Incident $incident, ?Model $attachable = null, ?User $uploader = null): Attachment
    {
        $cfg = config('seguridad_escolar.adjuntos');
        $realMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: 'application/octet-stream';

        if (! in_array($realMime, $cfg['mimes'], true)) {
            throw ValidationException::withMessages(['attachments' => 'El contenido del archivo «'.$file->getClientOriginalName().'» no corresponde a un tipo permitido.']);
        }

        $extension = match ($realMime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'docx',
        };
        $path = $cfg['directorio'].'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;

        Storage::disk($cfg['disco'])->putFileAs(dirname($path), $file, basename($path));

        $attachment = new Attachment([
            'original_name' => mb_substr($this->safeName($file->getClientOriginalName()), 0, 255),
            'disk' => $cfg['disco'],
            'stored_path' => $path,
            'mime_type' => $realMime,
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ]);
        $attachment->incident_id = $incident->id;
        $attachment->uploaded_by = $uploader?->id;
        if ($attachable) {
            $attachment->attachable()->associate($attachable);
        }
        $attachment->save();

        return $attachment;
    }

    /** Elimina caracteres de control y rutas del nombre original. */
    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? 'archivo';

        return $name === '' ? 'archivo' : $name;
    }
}
