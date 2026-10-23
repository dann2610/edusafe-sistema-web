<?php

namespace App\Enums;

/** Origen del reporte. */
enum ReportSource: string
{
    case Interno = 'interno';
    case Publico = 'publico';

    public function label(): string
    {
        return match ($this) {
            self::Interno => 'Registro interno',
            self::Publico => 'Formulario público',
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
