<?php

namespace App\Enums;

/** Estado de una institución en la plataforma. Desactivar nunca elimina sus datos. */
enum InstitutionStatus: string
{
    case EnPreparacion = 'en_preparacion';
    case Activa = 'activa';
    case Suspendida = 'suspendida';
    case Inactiva = 'inactiva';

    public function label(): string
    {
        return match ($this) {
            self::EnPreparacion => 'En preparación',
            self::Activa => 'Activa',
            self::Suspendida => 'Suspendida',
            self::Inactiva => 'Inactiva',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EnPreparacion => 'text-bg-info',
            self::Activa => 'text-bg-success',
            self::Suspendida => 'text-bg-warning',
            self::Inactiva => 'text-bg-secondary',
        };
    }

    /** El personal puede iniciar sesión (en preparación: para configurar). */
    public function allowsStaffLogin(): bool
    {
        return $this === self::Activa || $this === self::EnPreparacion;
    }

    /** La página pública y el formulario están disponibles. */
    public function isPublic(): bool
    {
        return $this === self::Activa;
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }

        return $out;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
