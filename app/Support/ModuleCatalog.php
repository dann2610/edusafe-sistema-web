<?php

namespace App\Support;

/**
 * Catálogo de módulos de la plataforma (fuente de verdad del código).
 * - implemented=false: el módulo existe solo como punto de expansión documentado
 *   y NO puede activarse.
 * - requires: módulos que deben estar activos para poder activarlo.
 * - default: estado inicial para instituciones nuevas.
 * Las funciones básicas (registro interno, bandeja, seguimientos, usuarios y
 * catálogos) son núcleo del sistema y no dependen de módulos.
 */
final class ModuleCatalog
{
    public const MODULES = [
        'reporte_publico' => [
            'name' => 'Formulario público de reportes',
            'description' => 'Página pública y formulario por pasos accesible desde el código QR, sin iniciar sesión.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['casos.bandeja'],
        ],
        'evidencias_publicas' => [
            'name' => 'Evidencias en el formulario público',
            'description' => 'Permite que quien reporta adjunte hasta 2 archivos (PDF, JPG o PNG). Se almacenan de forma privada.',
            'implemented' => true, 'requires' => ['reporte_publico'], 'default' => false,
            'permissions' => ['casos.ver_todos'],
        ],
        'consulta_estado' => [
            'name' => 'Consulta pública del estado',
            'description' => 'Quien reporta recibe una clave para consultar solo el estado de su reporte, sin ver detalles.',
            'implemented' => true, 'requires' => ['reporte_publico'], 'default' => true,
            'permissions' => [],
        ],
        'medidas' => [
            'name' => 'Medidas formativas, preventivas y de protección',
            'description' => 'Registro y seguimiento del cumplimiento de medidas en cada caso.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['medidas.gestionar'],
        ],
        'derivaciones' => [
            'name' => 'Derivaciones a entidades externas',
            'description' => 'Registro de derivaciones por canales oficiales y vista limitada para el personal de la entidad.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['derivaciones.gestionar', 'derivaciones.ver_entidad'],
        ],
        'dashboard' => [
            'name' => 'Dashboard de indicadores',
            'description' => 'Indicadores agregados y gráficos sin datos personales.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['dashboard.ver'],
        ],
        'reportes' => [
            'name' => 'Reportes PDF y Excel',
            'description' => 'Exportación de reportes agregados y, con permiso específico, del listado confidencial.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['reportes.generar', 'reportes.exportar_identificable'],
        ],
        'importacion_estudiantes' => [
            'name' => 'Importación de estudiantes por CSV',
            'description' => 'Carga masiva y atómica de estudiantes y matrículas.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['catalogos.gestionar'],
        ],
        'recordatorios' => [
            'name' => 'Recordatorios de seguimiento',
            'description' => 'Aviso diario a los responsables con seguimientos vencidos o del día.',
            'implemented' => true, 'requires' => [], 'default' => true,
            'permissions' => ['seguimientos.registrar'],
        ],
        'integracion_externa' => [
            'name' => 'Integración con sistemas externos',
            'description' => 'Punto de expansión futura (Ministerio de Educación, Policía Nacional, ECU 911). No existe integración: no puede activarse.',
            'implemented' => false, 'requires' => ['derivaciones'], 'default' => false,
            'permissions' => [],
        ],
    ];

    public static function keys(): array
    {
        return array_keys(self::MODULES);
    }
}
