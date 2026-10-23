<?php

namespace App\Enums;

/** Perfiles del sistema. El slug coincide con roles.slug. */
enum RoleSlug: string
{
    case Administrador = 'administrador';
    case Docente = 'docente';
    case Dece = 'dece';
    case Autoridad = 'autoridad';
    case Policia = 'policia';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador técnico',
            self::Docente => 'Docente o tutor',
            self::Dece => 'DECE',
            self::Autoridad => 'Autoridad institucional',
            self::Policia => 'Personal policial autorizado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Administrador => 'Administra cuentas, roles, catálogos y configuración técnica. No accede a expedientes confidenciales.',
            self::Docente => 'Registra incidentes y consulta el estado de sus propios reportes.',
            self::Dece => 'Revisa, prioriza, asigna y da seguimiento a los casos; registra medidas y derivaciones.',
            self::Autoridad => 'Consulta indicadores agregados y genera reportes institucionales.',
            self::Policia => 'Consulta únicamente los casos derivados a su entidad y la información compartida.',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
