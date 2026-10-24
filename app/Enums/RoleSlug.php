<?php

namespace App\Enums;

/** Perfiles del sistema. El slug coincide con roles.slug. */
enum RoleSlug: string
{
    case Superadmin = 'superadmin';
    case Administrador = 'administrador';
    case Docente = 'docente';
    case Dece = 'dece';
    case Autoridad = 'autoridad';
    case Policia = 'policia';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadministrador de plataforma',
            self::Administrador => 'Administrador institucional',
            self::Docente => 'Docente o tutor',
            self::Dece => 'DECE',
            self::Autoridad => 'Autoridad institucional',
            self::Policia => 'Personal policial autorizado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Superadmin => 'Administra instituciones, módulos, catálogos globales y estadísticas generales. No accede a expedientes.',
            self::Administrador => 'Administra usuarios, catálogos, módulos y configuración de su institución. No accede a expedientes confidenciales.',
            self::Docente => 'Registra incidentes, consulta el estado de sus reportes y da seguimiento a los casos asignados.',
            self::Dece => 'Revisa, prioriza, asigna y da seguimiento a los casos; registra medidas y derivaciones.',
            self::Autoridad => 'Consulta indicadores agregados y genera reportes institucionales.',
            self::Policia => 'Consulta únicamente los casos derivados a su entidad y la información compartida.',
        };
    }

    /** Roles que pertenecen a una institución (todos excepto el superadministrador). */
    public static function institutional(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r !== self::Superadmin));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
