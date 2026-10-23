<?php

namespace App\Enums;

/** Rol de un responsable asignado al caso. */
enum AssignmentRole: string
{
    case Principal = 'principal';
    case Apoyo = 'apoyo';

    public function label(): string
    {
        return match ($this) {
            self::Principal => 'Responsable principal',
            self::Apoyo => 'Apoyo',
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
