<?php

namespace App\Enums;

/** Prioridad del caso. "urgente" se asigna cuando el reporte indica riesgo inmediato. */
enum CasePriority: string
{
    case Normal = 'normal';
    case Alta = 'alta';
    case Urgente = 'urgente';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Alta => 'Alta',
            self::Urgente => 'Urgente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Normal => 'text-bg-light',
            self::Alta => 'text-bg-warning',
            self::Urgente => 'text-bg-danger',
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
