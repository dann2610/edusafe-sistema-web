<?php

namespace App\Enums;

/** Tipos de evento de la cronología del caso. */
enum CaseEventType: string
{
    case Creado = 'creado';
    case ParticipanteAgregado = 'participante_agregado';
    case Asignado = 'asignado';
    case AsignacionLiberada = 'asignacion_liberada';
    case EstadoCambiado = 'estado_cambiado';
    case Seguimiento = 'seguimiento';
    case Correccion = 'correccion';
    case MedidaRegistrada = 'medida_registrada';
    case MedidaActualizada = 'medida_actualizada';
    case Derivacion = 'derivacion';
    case DerivacionActualizada = 'derivacion_actualizada';
    case Adjunto = 'adjunto';
    case Cerrado = 'cerrado';
    case Reabierto = 'reabierto';

    public function label(): string
    {
        return match ($this) {
            self::Creado => 'Caso registrado',
            self::ParticipanteAgregado => 'Involucrado agregado',
            self::Asignado => 'Responsable asignado',
            self::AsignacionLiberada => 'Asignación finalizada',
            self::EstadoCambiado => 'Cambio de estado',
            self::Seguimiento => 'Seguimiento registrado',
            self::Correccion => 'Corrección registrada',
            self::MedidaRegistrada => 'Medida registrada',
            self::MedidaActualizada => 'Medida actualizada',
            self::Derivacion => 'Derivación registrada',
            self::DerivacionActualizada => 'Derivación actualizada',
            self::Adjunto => 'Evidencia adjuntada',
            self::Cerrado => 'Caso cerrado',
            self::Reabierto => 'Caso reabierto',
        };
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
