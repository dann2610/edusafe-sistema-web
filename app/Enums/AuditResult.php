<?php

namespace App\Enums;

/** Resultado de una operación auditada. */
enum AuditResult: string
{
    case Exito = 'exito';
    case Denegado = 'denegado';
    case Fallido = 'fallido';

    public function label(): string
    {
        return match ($this) {
            self::Exito => 'Éxito',
            self::Denegado => 'Denegado',
            self::Fallido => 'Fallido',
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
