<?php

namespace App\Enums;

/** Medio utilizado para la derivación (sin integración automática con sistemas externos). */
enum ReferralChannel: string
{
    case Oficio = 'oficio';
    case CorreoInstitucional = 'correo_institucional';
    case Presencial = 'presencial';
    case Telefonico = 'telefonico';
    case PlataformaOficial = 'plataforma_oficial';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Oficio => 'Oficio',
            self::CorreoInstitucional => 'Correo institucional',
            self::Presencial => 'Presencial',
            self::Telefonico => 'Telefónico',
            self::PlataformaOficial => 'Plataforma oficial de la entidad',
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
