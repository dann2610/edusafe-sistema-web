<?php

namespace App\Enums;

/**
 * Tipos permitidos para los campos adicionales de una categoría.
 * Solo datos: no existe ningún tipo que permita HTML, JavaScript o SQL.
 */
enum FieldType: string
{
    case Texto = 'texto';
    case TextoLargo = 'texto_largo';
    case Fecha = 'fecha';
    case Hora = 'hora';
    case Lista = 'lista';
    case Casilla = 'casilla';

    public function label(): string
    {
        return match ($this) {
            self::Texto => 'Texto corto',
            self::TextoLargo => 'Texto largo',
            self::Fecha => 'Fecha',
            self::Hora => 'Hora',
            self::Lista => 'Lista de opciones',
            self::Casilla => 'Casilla (sí/no)',
        };
    }

    /** Reglas de validación del valor ingresado. */
    public function rules(bool $required, array $options = []): array
    {
        $base = $this === self::Casilla ? ['nullable', 'boolean'] : [$required ? 'required' : 'nullable'];

        return array_merge($base, match ($this) {
            self::Texto => ['string', 'max:200'],
            self::TextoLargo => ['string', 'max:2000'],
            self::Fecha => ['date', 'before_or_equal:today'],
            self::Hora => ['date_format:H:i'],
            self::Lista => ['string', 'in:'.implode(',', array_map(fn ($o) => str_replace(',', '', $o), $options))],
            self::Casilla => [],
        });
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
