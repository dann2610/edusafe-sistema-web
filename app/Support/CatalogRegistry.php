<?php

namespace App\Support;

use App\Enums\ExternalEntityType;
use App\Enums\MeasureKind;
use App\Models\ExternalEntity;
use App\Models\FollowUpType;
use App\Models\IncidentCategory;
use App\Models\MeasureType;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Definición de los catálogos simples administrados con un controlador genérico.
 * Cada catálogo declara su modelo, campos, reglas y columnas del listado.
 * Los registros no se eliminan: se desactivan para conservar el historial.
 */
final class CatalogRegistry
{
    public static function all(): array
    {
        return [
            'categorias' => [
                'model' => IncidentCategory::class,
                'title' => 'Categorías de incidentes',
                'singular' => 'categoría',
                'icon' => 'bi-tags',
                'order' => [['sort_order', 'asc'], ['name', 'asc']],
                'columns' => ['code' => 'Código', 'name' => 'Nombre', 'suggests_high_priority' => 'Prioridad alta', 'available_in_public_form' => 'En formulario público'],
                'fields' => [
                    'code' => ['label' => 'Código', 'type' => 'text', 'required' => true, 'unique' => true, 'max' => 30,
                        'extra' => ['regex:/^[A-Z0-9_]+$/'], 'help' => 'Mayúsculas, números y guion bajo. Ejemplo: CIBERACOSO.'],
                    'name' => ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'unique' => true, 'max' => 120],
                    'description' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 500,
                        'help' => 'Se muestra como orientación en los formularios.'],
                    'suggests_high_priority' => ['label' => 'Sugerir prioridad alta al registrar', 'type' => 'checkbox'],
                    'available_in_public_form' => ['label' => 'Disponible en el formulario público', 'type' => 'checkbox', 'default' => true],
                    'sort_order' => ['label' => 'Orden', 'type' => 'number', 'min' => 0, 'max' => 9999, 'default' => 100],
                ],
            ],
            'entidades' => [
                'model' => ExternalEntity::class,
                'title' => 'Entidades externas',
                'singular' => 'entidad',
                'icon' => 'bi-building',
                'order' => [['name', 'asc']],
                'columns' => ['name' => 'Nombre', 'type' => 'Tipo', 'institutional_phone' => 'Teléfono', 'institutional_email' => 'Correo'],
                'fields' => [
                    'name' => ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'unique' => true, 'max' => 150],
                    'type' => ['label' => 'Tipo', 'type' => 'select', 'required' => true, 'options' => ExternalEntityType::options()],
                    'institutional_phone' => ['label' => 'Teléfono institucional', 'type' => 'text', 'max' => 30],
                    'institutional_email' => ['label' => 'Correo institucional', 'type' => 'email', 'max' => 150],
                    'address' => ['label' => 'Dirección', 'type' => 'text', 'max' => 255],
                    'notes' => ['label' => 'Observaciones', 'type' => 'textarea', 'max' => 500,
                        'help' => 'Solo datos institucionales de contacto, nunca datos personales.'],
                ],
            ],
            'tipos-medida' => [
                'model' => MeasureType::class,
                'title' => 'Tipos de medida',
                'singular' => 'tipo de medida',
                'icon' => 'bi-shield-plus',
                'order' => [['kind', 'asc'], ['name', 'asc']],
                'columns' => ['name' => 'Nombre', 'kind' => 'Clase'],
                'fields' => [
                    'name' => ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'unique' => true, 'max' => 120],
                    'kind' => ['label' => 'Clase de medida', 'type' => 'select', 'required' => true, 'options' => MeasureKind::options()],
                    'description' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 500],
                ],
            ],
            'tipos-actuacion' => [
                'model' => FollowUpType::class,
                'title' => 'Tipos de actuación',
                'singular' => 'tipo de actuación',
                'icon' => 'bi-list-check',
                'order' => [['name', 'asc']],
                'columns' => ['name' => 'Nombre', 'description' => 'Descripción'],
                'fields' => [
                    'name' => ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'unique' => true, 'max' => 120],
                    'description' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 500],
                ],
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function get(string $key): array
    {
        $all = self::all();
        if (! isset($all[$key])) {
            throw new NotFoundHttpException;
        }

        return $all[$key] + ['key' => $key];
    }

    /** Reglas de validación construidas a partir de la definición. */
    public static function rules(array $catalog, ?int $ignoreId = null): array
    {
        $model = new $catalog['model'];
        $rules = [];
        foreach ($catalog['fields'] as $name => $f) {
            $r = [];
            if ($f['type'] === 'checkbox') {
                $rules[$name] = ['boolean'];

                continue;
            }
            $r[] = ($f['required'] ?? false) ? 'required' : 'nullable';
            $r[] = match ($f['type']) {
                'number' => 'integer',
                'email' => 'email',
                default => 'string',
            };
            if (isset($f['max'])) {
                $r[] = ($f['type'] === 'number' ? 'max:' : 'max:').$f['max'];
            }
            if (isset($f['min'])) {
                $r[] = 'min:'.$f['min'];
            }
            if ($f['type'] === 'select') {
                $r[] = Rule::in(array_keys($f['options']));
            }
            if ($f['unique'] ?? false) {
                $r[] = Rule::unique($model->getTable(), $name)->ignore($ignoreId);
            }
            foreach ($f['extra'] ?? [] as $extra) {
                $r[] = $extra;
            }
            $rules[$name] = $r;
        }

        return $rules;
    }

    /** Valor mostrado en una celda del listado. */
    public static function display(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'Sí' : 'No',
            $value instanceof \BackedEnum && method_exists($value, 'label') => $value->label(),
            $value === null || $value === '' => '—',
            default => (string) $value,
        };
    }
}
