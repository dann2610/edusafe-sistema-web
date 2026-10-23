<?php

namespace Tests\Unit;

use App\Services\AuditLogger;
use PHPUnit\Framework\TestCase;

class AuditLoggerSanitizeTest extends TestCase
{
    public function test_elimina_claves_sensibles_incluso_anidadas(): void
    {
        $clean = AuditLogger::sanitize([
            'campos' => ['name', 'email'],
            'password' => 'secreta',
            'Token' => 'abc',
            'detalle' => ['description' => 'texto de denuncia', 'motivo' => 'ok', 'current_password' => 'x'],
        ]);

        $this->assertSame(['campos' => ['name', 'email'], 'detalle' => ['motivo' => 'ok']], $clean);
    }

    public function test_recorta_textos_largos(): void
    {
        $clean = AuditLogger::sanitize(['motivo' => str_repeat('a', 500)]);
        $this->assertSame(200, mb_strlen($clean['motivo']));
    }
}
