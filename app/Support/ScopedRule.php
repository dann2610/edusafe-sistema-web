<?php

namespace App\Support;

use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

/**
 * Reglas "exists" limitadas a la institución del contexto.
 * Las reglas de validación consultan la base directamente (sin el alcance
 * global de Eloquent), por lo que SIEMPRE deben usar estos ayudantes para
 * impedir que se envíe el identificador de un registro de otra institución.
 */
final class ScopedRule
{
    /** El registro debe pertenecer a la institución actual. */
    public static function own(string $table, string $column = 'id'): Exists
    {
        $id = app(TenantContext::class)->id();

        return Rule::exists($table, $column)->where('institution_id', $id ?? 0);
    }

    /** El registro debe ser de la institución actual o global (catálogos). */
    public static function ownOrGlobal(string $table, string $column = 'id'): Exists
    {
        $id = app(TenantContext::class)->id();

        return Rule::exists($table, $column)->where(function ($q) use ($id) {
            $q->whereNull('institution_id');
            if ($id !== null) {
                $q->orWhere('institution_id', $id);
            }
        });
    }
}
