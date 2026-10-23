<?php

namespace App\Services;

use App\Enums\AuditResult;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registro de operaciones críticas en la bitácora inmutable.
 *
 * Nunca guarda contraseñas, tokens ni descripciones de denuncias: las claves
 * sensibles se eliminan de metadata antes de persistir. Un fallo al escribir
 * la bitácora se registra en el log técnico pero no interrumpe la operación
 * del usuario (salvo que se use mustLog()).
 */
class AuditLogger
{
    /** Claves que jamás se almacenan en metadata (comparación sin mayúsculas). */
    private const FORBIDDEN_KEYS = [
        'password', 'password_confirmation', 'current_password', 'new_password',
        'token', 'remember_token', '_token', 'api_key', 'secret',
        'description', 'descripcion', 'observations', 'shared_summary', 'closure_summary',
    ];

    public function __construct(private readonly Request $request) {}

    public function success(string $action, ?Model $auditable = null, array $metadata = [], ?User $actor = null): ?AuditLog
    {
        return $this->write($action, AuditResult::Exito, $auditable, $metadata, $actor);
    }

    public function denied(string $action, ?Model $auditable = null, array $metadata = [], ?User $actor = null): ?AuditLog
    {
        return $this->write($action, AuditResult::Denegado, $auditable, $metadata, $actor);
    }

    public function failed(string $action, ?Model $auditable = null, array $metadata = [], ?User $actor = null): ?AuditLog
    {
        return $this->write($action, AuditResult::Fallido, $auditable, $metadata, $actor);
    }

    /** Variante que propaga el error: usar dentro de transacciones donde la auditoría es obligatoria. */
    public function mustLog(string $action, AuditResult $result, ?Model $auditable = null, array $metadata = [], ?User $actor = null): AuditLog
    {
        return AuditLog::create($this->payload($action, $result, $auditable, $metadata, $actor));
    }

    private function write(string $action, AuditResult $result, ?Model $auditable, array $metadata, ?User $actor): ?AuditLog
    {
        try {
            return AuditLog::create($this->payload($action, $result, $auditable, $metadata, $actor));
        } catch (Throwable $e) {
            Log::error('No se pudo registrar la auditoría', ['action' => $action, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function payload(string $action, AuditResult $result, ?Model $auditable, array $metadata, ?User $actor): array
    {
        $actor ??= Auth::user();
        $clean = self::sanitize($metadata);

        return [
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $auditable ? class_basename($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'result' => $result->value,
            'ip_address' => config('seguridad_escolar.auditoria.registrar_ip') ? $this->request->ip() : null,
            'metadata' => $clean === [] ? null : $clean,
        ];
    }

    /** Elimina recursivamente claves sensibles y recorta textos largos. */
    public static function sanitize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::FORBIDDEN_KEYS, true)) {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = self::sanitize($value);
            } elseif (is_string($value)) {
                $out[$key] = mb_substr($value, 0, 200);
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
