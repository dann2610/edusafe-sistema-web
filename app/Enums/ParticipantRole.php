<?php

namespace App\Enums;

/** Rol de una persona dentro del caso. "Señalado" no implica responsabilidad: el sistema no la determina. */
enum ParticipantRole: string
{
    case Afectado = 'afectado';
    case Senalado = 'senalado';
    case Testigo = 'testigo';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Afectado => 'Persona afectada',
            self::Senalado => 'Persona señalada',
            self::Testigo => 'Testigo',
            self::Otro => 'Otro',
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
