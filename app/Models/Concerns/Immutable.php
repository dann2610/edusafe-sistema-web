<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Impide modificar o eliminar registros históricos desde la aplicación.
 * La base de datos aplica la misma regla mediante el trigger prevent_modification().
 */
trait Immutable
{
    public static function bootImmutable(): void
    {
        static::updating(function ($model) {
            throw new LogicException(class_basename($model).' es un registro inmutable y no puede modificarse.');
        });

        static::deleting(function ($model) {
            throw new LogicException(class_basename($model).' es un registro inmutable y no puede eliminarse.');
        });
    }
}
