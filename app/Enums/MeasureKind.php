<?php

namespace App\Enums;

/** Tipo de medida institucional. */
enum MeasureKind: string
{
    case Formativa = 'formativa';
    case Preventiva = 'preventiva';
    case Proteccion = 'proteccion';

    public function label(): string
    {
        return match ($this) {
            self::Formativa => 'Formativa',
            self::Preventiva => 'Preventiva',
            self::Proteccion => 'De protección',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
