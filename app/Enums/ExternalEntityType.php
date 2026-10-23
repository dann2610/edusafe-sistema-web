<?php

namespace App\Enums;

/** Tipo de entidad externa. */
enum ExternalEntityType: string
{
    case Policia = 'policia';
    case Fiscalia = 'fiscalia';
    case JuntaProteccion = 'junta_proteccion';
    case Salud = 'salud';
    case DistritoEducativo = 'distrito_educativo';
    case Dinapen = 'dinapen';
    case Otra = 'otra';

    public function label(): string
    {
        return match ($this) {
            self::Policia => 'Policía Nacional',
            self::Fiscalia => 'Fiscalía',
            self::JuntaProteccion => 'Junta Cantonal de Protección de Derechos',
            self::Salud => 'Salud',
            self::DistritoEducativo => 'Distrito educativo',
            self::Dinapen => 'DINAPEN',
            self::Otra => 'Otra',
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
