<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Evidencia adjunta. stored_path es relativo al disco privado y nunca se expone.
 * La descarga se hace con un controlador que verifica la Policy del caso.
 */
class Attachment extends Model
{
    protected $fillable = ['original_name', 'disk', 'stored_path', 'mime_type', 'size_bytes', 'sha256'];

    protected $hidden = ['stored_path', 'disk', 'sha256'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        $kb = $this->size_bytes / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1, ',', '.').' MB' : number_format($kb, 0, ',', '.').' KB';
    }
}
