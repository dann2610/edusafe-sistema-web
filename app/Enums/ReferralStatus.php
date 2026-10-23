<?php

namespace App\Enums;

/** Estado de la gestión de una derivación. */
enum ReferralStatus: string
{
    case Enviada = 'enviada';
    case Recibida = 'recibida';
    case EnTramite = 'en_tramite';
    case Respondida = 'respondida';
    case Finalizada = 'finalizada';

    public function label(): string
    {
        return match ($this) {
            self::Enviada => 'Enviada',
            self::Recibida => 'Recibida',
            self::EnTramite => 'En trámite',
            self::Respondida => 'Respondida',
            self::Finalizada => 'Finalizada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Enviada => 'text-bg-secondary',
            self::Recibida => 'text-bg-info',
            self::EnTramite => 'text-bg-primary',
            self::Respondida => 'text-bg-success',
            self::Finalizada => 'text-bg-dark',
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
