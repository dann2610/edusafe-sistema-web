<?php

namespace Tests\Unit;

use App\Enums\CaseStatus;
use PHPUnit\Framework\TestCase;

class CaseStatusTest extends TestCase
{
    public function test_un_caso_registrado_solo_puede_pasar_a_revision(): void
    {
        $this->assertSame([CaseStatus::EnRevision], CaseStatus::Registrado->allowedTransitions());
        $this->assertFalse(CaseStatus::Registrado->canTransitionTo(CaseStatus::Cerrado));
        $this->assertFalse(CaseStatus::Registrado->canTransitionTo(CaseStatus::Asignado));
    }

    public function test_asignar_o_derivar_no_cierra_el_caso_automaticamente(): void
    {
        // Desde "asignado" no existe transición directa a "cerrado"
        $this->assertFalse(CaseStatus::Asignado->canTransitionTo(CaseStatus::Cerrado));
        // Un caso derivado puede seguir en seguimiento; el cierre es una transición explícita
        $this->assertTrue(CaseStatus::Derivado->canTransitionTo(CaseStatus::EnSeguimiento));
        $this->assertTrue(CaseStatus::Derivado->isOpen());
    }

    public function test_un_caso_cerrado_solo_se_reabre_a_revision_con_justificacion(): void
    {
        $this->assertSame([CaseStatus::EnRevision], CaseStatus::Cerrado->allowedTransitions());
        $this->assertTrue(CaseStatus::Cerrado->requiresJustificationTo(CaseStatus::EnRevision));
        $this->assertFalse(CaseStatus::Cerrado->isOpen());
    }

    public function test_el_cierre_y_la_derivacion_exigen_justificacion(): void
    {
        $this->assertTrue(CaseStatus::EnSeguimiento->requiresJustificationTo(CaseStatus::Cerrado));
        $this->assertTrue(CaseStatus::EnRevision->requiresJustificationTo(CaseStatus::Derivado));
        $this->assertFalse(CaseStatus::Registrado->requiresJustificationTo(CaseStatus::EnRevision));
    }

    public function test_ningun_estado_transiciona_a_si_mismo_y_todos_tienen_salida(): void
    {
        foreach (CaseStatus::cases() as $status) {
            $this->assertNotContains($status, $status->allowedTransitions(), $status->value);
            $this->assertNotEmpty($status->allowedTransitions(), $status->value);
            $this->assertNotSame('', $status->label());
        }
    }

    public function test_todos_los_estados_son_alcanzables_desde_registrado(): void
    {
        $visited = [CaseStatus::Registrado->value => true];
        $queue = [CaseStatus::Registrado];
        while ($queue) {
            foreach (array_shift($queue)->allowedTransitions() as $next) {
                if (! isset($visited[$next->value])) {
                    $visited[$next->value] = true;
                    $queue[] = $next;
                }
            }
        }
        $this->assertEqualsCanonicalizing(CaseStatus::values(), array_keys($visited));
    }
}
