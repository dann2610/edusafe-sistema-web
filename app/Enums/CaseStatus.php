<?php

namespace App\Enums;

/**
 * Estados del caso y transiciones permitidas.
 * Regla de negocio central: ninguna asignación ni derivación cierra el caso
 * automáticamente; el cierre es siempre una transición explícita y justificada.
 */
enum CaseStatus: string
{
    case Registrado = 'registrado';
    case EnRevision = 'en_revision';
    case RequiereInformacion = 'requiere_informacion';
    case Asignado = 'asignado';
    case EnSeguimiento = 'en_seguimiento';
    case Derivado = 'derivado';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Registrado => 'Registrado',
            self::EnRevision => 'En revisión',
            self::RequiereInformacion => 'Requiere información',
            self::Asignado => 'Asignado',
            self::EnSeguimiento => 'En seguimiento',
            self::Derivado => 'Derivado',
            self::Cerrado => 'Cerrado',
        };
    }

    /** Clase de Bootstrap para la insignia de estado. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Registrado => 'text-bg-secondary',
            self::EnRevision => 'text-bg-info',
            self::RequiereInformacion => 'text-bg-warning',
            self::Asignado => 'text-bg-primary',
            self::EnSeguimiento => 'text-bg-primary',
            self::Derivado => 'text-bg-dark',
            self::Cerrado => 'text-bg-success',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Registrado => [self::EnRevision],
            self::EnRevision => [self::RequiereInformacion, self::Asignado, self::Derivado, self::Cerrado],
            self::RequiereInformacion => [self::EnRevision],
            self::Asignado => [self::EnSeguimiento, self::RequiereInformacion, self::Derivado],
            self::EnSeguimiento => [self::RequiereInformacion, self::Derivado, self::Cerrado],
            self::Derivado => [self::EnSeguimiento, self::Cerrado],
            self::Cerrado => [self::EnRevision], // reapertura
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Transiciones que exigen justificación escrita. */
    public function requiresJustificationTo(self $target): bool
    {
        return $target === self::Cerrado
            || $target === self::RequiereInformacion
            || $target === self::Derivado
            || ($this === self::Cerrado && $target === self::EnRevision);
    }

    public function isOpen(): bool
    {
        return $this !== self::Cerrado;
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
