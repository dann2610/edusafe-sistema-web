<?php

namespace Database\Seeders;

use App\Models\ExternalEntity;
use App\Models\FollowUpType;
use App\Models\IncidentCategory;
use App\Models\MeasureType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos iniciales. Son valores de partida editables desde el módulo de
 * catálogos; la institución puede agregar, renombrar o desactivar registros.
 * Las entidades externas se crean sin datos de contacto: deben completarse
 * con la información oficial verificada por la institución.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = [
                ['ACOSO', 'Acoso escolar', 'Agresiones reiteradas e intencionales entre estudiantes.', false],
                ['VIOL_FISICA', 'Violencia física', 'Golpes, empujones u otras agresiones físicas.', true],
                ['VIOL_VERBAL', 'Violencia verbal o psicológica', 'Insultos, humillaciones, intimidación o exclusión.', false],
                ['AMENAZA', 'Amenazas', 'Amenazas contra la integridad de una persona de la comunidad educativa.', true],
                ['CIBERACOSO', 'Ciberacoso', 'Agresiones mediante redes sociales, mensajería u otros medios digitales.', false],
                ['DISCRIMINACION', 'Discriminación', 'Trato desigual por condición personal, origen, género u otra condición.', false],
                ['VIOL_SEXUAL', 'Presunta violencia sexual', 'Requiere aplicación inmediata del protocolo oficial vigente.', true],
                ['SUSTANCIAS', 'Situación de riesgo con sustancias', 'Situaciones de riesgo relacionadas con alcohol, tabaco u otras drogas.', true],
                ['OTRA', 'Otra situación de riesgo', 'Situaciones que no corresponden a las categorías anteriores.', false],
            ];
            foreach ($categories as $i => [$code, $name, $desc, $high]) {
                IncidentCategory::updateOrCreate(['code' => $code], [
                    'name' => $name,
                    'description' => $desc,
                    'suggests_high_priority' => $high,
                    'available_in_public_form' => true,
                    'sort_order' => ($i + 1) * 10,
                    'is_active' => true,
                ]);
            }

            $measures = [
                ['Acompañamiento psicoeducativo', 'formativa'],
                ['Taller de convivencia y resolución de conflictos', 'formativa'],
                ['Acuerdo de convivencia', 'formativa'],
                ['Comunicación y reunión con representantes', 'preventiva'],
                ['Plan de vigilancia en espacios comunes', 'preventiva'],
                ['Campaña de prevención en el curso', 'preventiva'],
                ['Separación de espacios entre las partes', 'proteccion'],
                ['Acompañamiento en ingreso y salida', 'proteccion'],
                ['Activación de protocolo oficial', 'proteccion'],
            ];
            foreach ($measures as [$name, $kind]) {
                MeasureType::updateOrCreate(['name' => $name], ['kind' => $kind, 'is_active' => true]);
            }

            $followUps = [
                'Entrevista con estudiante',
                'Entrevista con representante',
                'Reunión con docente o tutor',
                'Observación en aula o recreo',
                'Llamada telefónica',
                'Revisión documental',
                'Reunión de equipo DECE',
                'Otra actuación',
            ];
            foreach ($followUps as $name) {
                FollowUpType::updateOrCreate(['name' => $name], ['is_active' => true]);
            }

            $entities = [
                ['Policía Nacional — Unidad de Policía Comunitaria (UPC) del sector', 'policia'],
                ['DINAPEN', 'dinapen'],
                ['Fiscalía General del Estado', 'fiscalia'],
                ['Junta Cantonal de Protección de Derechos', 'junta_proteccion'],
                ['Dirección Distrital de Educación', 'distrito_educativo'],
                ['Centro de Salud del sector', 'salud'],
            ];
            foreach ($entities as [$name, $type]) {
                ExternalEntity::updateOrCreate(['name' => $name], [
                    'type' => $type,
                    'notes' => 'Completar contacto institucional verificado.',
                    'is_active' => true,
                ]);
            }
        });
    }
}
