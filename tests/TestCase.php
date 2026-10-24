<?php

namespace Tests;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fuera de una petición HTTP, las pruebas trabajan en la institución por
     * defecto (como lo haría un usuario de esa institución). Cada petición
     * reinicia el contexto (ResetRequestState) y lo toma del usuario autenticado.
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (\Illuminate\Support\Facades\Schema::hasTable('institutions') && ($default = \App\Models\Institution::default())) {
            app(\App\Support\TenantContext::class)->set($default);
        }
    }

    /**
     * Ejecuta una operación que DEBE fallar dentro de un SAVEPOINT, para que el
     * error de PostgreSQL no aborte la transacción de RefreshDatabase.
     * Devuelve la excepción capturada para inspeccionarla.
     */
    protected function assertDatabaseRejects(Closure $operation, string $expectedClass = QueryException::class): Throwable
    {
        try {
            DB::transaction($operation);
        } catch (Throwable $e) {
            $this->assertInstanceOf($expectedClass, $e, 'Se esperaba '.$expectedClass.' y se obtuvo '.get_class($e).': '.$e->getMessage());

            return $e;
        }

        $this->fail('La operación debía ser rechazada y se ejecutó correctamente.');
    }
}
