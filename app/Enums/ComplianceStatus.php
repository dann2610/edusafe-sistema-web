<?php

namespace App\Enums;

/** Estado de cumplimiento de una medida. */
enum ComplianceStatus: string
{
    case Pendiente = 'pendiente';
    case EnEjecucion = 'en_ejecucion';
    case Cumplida = 'cumplida';
    case Incumplida = 'incumplida';
    case Suspendida = 'suspendida';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnEjecucion => 'En ejecución',
            self::Cumplida => 'Cumplida',
            self::Incumplida => 'Incumplida',
            self::Suspendida => 'Suspendida',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendiente => 'text-bg-secondary',
            self::EnEjecucion => 'text-bg-primary',
            self::Cumplida => 'text-bg-success',
            self::Incumplida => 'text-bg-danger',
            self::Suspendida => 'text-bg-dark',
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
