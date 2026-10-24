<?php

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Campo adicional de una categoría. Define datos, nunca código: el tipo es un
 * valor de FieldType y las opciones son textos planos que se muestran escapados.
 */
class IncidentCategoryField extends Model
{
    protected $fillable = ['key', 'label', 'type', 'options', 'is_required', 'show_in_public_form', 'help_text', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'show_in_public_form' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'incident_category_id');
    }

    public function inputName(): string
    {
        return 'extra.'.$this->key;
    }

    public function rules(): array
    {
        return $this->type->rules($this->is_required, $this->options ?? []);
    }

    /** Valor legible para mostrar en el expediente. */
    public function display(mixed $value): string
    {
        return match ($this->type) {
            FieldType::Casilla => $value ? 'Sí' : 'No',
            FieldType::Fecha => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—',
            default => filled($value) ? (string) $value : '—',
        };
    }
}
