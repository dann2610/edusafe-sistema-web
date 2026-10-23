<?php

namespace App\Enums;

/** Relación de quien reporta con la institución (reportes públicos). */
enum ReporterRelationship: string
{
    case Estudiante = 'estudiante';
    case Representante = 'representante';
    case Docente = 'docente';
    case Personal = 'personal';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Representante => 'Madre, padre o representante',
            self::Docente => 'Docente',
            self::Personal => 'Personal de la institución',
            self::Otro => 'Otra persona',
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
