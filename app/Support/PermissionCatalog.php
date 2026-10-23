<?php

namespace App\Support;

/**
 * Catálogo único de permisos del sistema (fuente de verdad).
 * - El seeder lo usa para poblar las tablas permissions y permission_role.
 * - AppServiceProvider registra una Gate por cada slug.
 * Mínimo privilegio: el administrador técnico no tiene permisos sobre expedientes.
 */
final class PermissionCatalog
{
    /** slug => [nombre, módulo, roles] */
    public const MATRIX = [
        // Administración técnica
        'usuarios.gestionar' => ['Gestionar cuentas y roles', 'usuarios', ['administrador']],
        'catalogos.gestionar' => ['Gestionar catálogos institucionales', 'catalogos', ['administrador']],
        'configuracion.gestionar' => ['Gestionar configuración técnica y código QR', 'configuracion', ['administrador']],
        'auditoria.consultar' => ['Consultar bitácora de auditoría', 'auditoria', ['administrador', 'autoridad']],

        // Registro de incidentes
        'incidentes.registrar' => ['Registrar incidentes', 'incidentes', ['docente', 'dece', 'autoridad']],
        'incidentes.ver_propios' => ['Consultar los reportes propios', 'incidentes', ['docente', 'dece', 'autoridad']],
        'adjuntos.subir' => ['Adjuntar evidencias permitidas', 'incidentes', ['docente', 'dece']],
        'estudiantes.buscar' => ['Buscar estudiantes para vincularlos a un caso', 'incidentes', ['docente', 'dece']],

        // Gestión de casos (DECE)
        'casos.bandeja' => ['Acceder a la bandeja de casos', 'casos', ['dece']],
        'casos.ver_todos' => ['Consultar el expediente de cualquier caso', 'casos', ['dece']],
        'casos.gestionar' => ['Revisar, priorizar y cambiar estados', 'casos', ['dece']],
        'casos.asignar' => ['Asignar responsables', 'casos', ['dece']],
        'seguimientos.registrar' => ['Registrar seguimientos (DECE o responsable asignado)', 'casos', ['dece', 'docente']],
        'medidas.gestionar' => ['Registrar y actualizar medidas', 'casos', ['dece']],
        'derivaciones.gestionar' => ['Registrar y actualizar derivaciones', 'casos', ['dece']],

        // Entidad externa
        'derivaciones.ver_entidad' => ['Consultar casos derivados a su entidad', 'derivaciones', ['policia']],

        // Indicadores y reportes
        'dashboard.ver' => ['Consultar el dashboard de indicadores agregados', 'reportes', ['dece', 'autoridad']],
        'reportes.generar' => ['Generar reportes agregados PDF/Excel', 'reportes', ['dece', 'autoridad']],
        'reportes.exportar_identificable' => ['Exportar información identificable', 'reportes', ['dece']],
    ];

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::MATRIX);
    }

    public static function exists(string $slug): bool
    {
        return array_key_exists($slug, self::MATRIX);
    }

    /** @return list<string> */
    public static function forRole(string $roleSlug): array
    {
        return array_keys(array_filter(self::MATRIX, fn ($def) => in_array($roleSlug, $def[2], true)));
    }
}
