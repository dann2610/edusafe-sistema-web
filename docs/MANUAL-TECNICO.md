# Manual técnico

## 1. Stack y versiones verificadas

Laravel 12 (framework 12.70) sobre PHP 8.2 o superior (probado en 8.3.6 y 8.3.30). PostgreSQL 14 o superior (probado en 16 y 17). Blade con Bootstrap 5.3.8, Bootstrap Icons 1.13.2 y Chart.js 4.6.1, servidos localmente desde `public/vendor` (sin CDN). PDF con barryvdh/laravel-dompdf, Excel con openspout/openspout y QR con bacon/bacon-qr-code. Pruebas con PHPUnit 11.

Extensiones de PHP requeridas: `pdo_pgsql`, `pgsql`, `mbstring`, `fileinfo`, `openssl`, `zip`, `gd`, `intl`.

## 2. Estructura

```
app/
  Console/Commands/      tic:crear-admin, tic:recordatorios-seguimiento
  Enums/                 Estados del caso (con transiciones), roles y catálogos fijos
  Http/Controllers/      Controladores delgados (públicos, casos, reportes, admin)
  Http/Middleware/       SecurityHeaders, EnsureUserIsActive, EnsurePasswordIsChanged
  Http/Requests/         Validación: login, usuario, incidente interno, reporte público
  Models/                21 modelos Eloquent; Concerns/Immutable
  Notifications/         Avisos internos (canal database) y correo de recuperación
  Policies/              UserPolicy, IncidentPolicy, AttachmentPolicy, ReferralPolicy
  Services/              IncidentRegistrar, CaseWorkflow, AttachmentStorage, AuditLogger,
                         StatisticsService, ReportExporter, QrCodeService, UserAccountService
  Support/               PermissionCatalog (matriz de permisos), CatalogRegistry
config/seguridad_escolar.php   Parámetros propios (institución, QR, adjuntos, límites)
database/migrations/           12 migraciones propias + 3 base de Laravel
database/seeders/              Roles, catálogos, datos DEMO (solo local)
lang/es/                       Validación, autenticación y paginación en español
resources/views/               67 vistas Blade y componentes
routes/web.php                 87 rutas: públicas, de autenticación y privadas
tests/                         111 pruebas: unitarias y de funcionalidad
```

## 3. Seguridad implementada

| Control | Implementación |
|---|---|
| Contraseñas | bcrypt (cast `hashed`); política de 12 caracteres con mayúsculas, minúsculas, números y símbolos |
| Fuerza bruta | 5 intentos por correo e IP con bloqueo de 5 minutos, más `throttle` por ruta |
| Enumeración de cuentas | Mensajes genéricos en el login y la recuperación; tiempo de respuesta igualado |
| Sesiones | Cifradas, 60 minutos, regeneradas al iniciar sesión, invalidadas al salir y cerradas al desactivar la cuenta o cambiar el rol |
| Autorización | Gate por permiso, Policies por registro y verificación de pertenencia en subrecursos (medida, derivación o seguimiento del caso de la URL) |
| CSRF, XSS, SQL | Token CSRF de Laravel; escape `{{ }}` en Blade; Eloquent y consultas parametrizadas |
| Cabeceras | CSP sin scripts en línea, X-Frame-Options DENY, nosniff, Referrer-Policy, HSTS bajo HTTPS y `no-store` en páginas privadas |
| Adjuntos | Extensión, tamaño y tipo real verificados con finfo; nombre aleatorio; disco privado `storage/app/private`; descarga autorizada y auditada; hash SHA-256 |
| Formulario público | Campo trampa, token cifrado con tiempo mínimo y máximo, límite por IP y hora, sin adjuntos |
| Integridad | Restricciones CHECK, FK RESTRICT e índices únicos parciales; triggers que impiden alterar historial, bitácora, seguimientos y eliminar casos |
| Bitácora | Saneamiento de claves sensibles; nunca guarda contraseñas, tokens ni descripciones |
| Exportaciones | Neutralización de fórmulas en Excel; listado identificable solo con permiso específico y auditado |
| Mínimo privilegio | El administrador técnico no accede a expedientes; la policía solo ve el resumen compartido con su entidad |

## 4. Reglas de negocio principales

- Las transiciones de estado están definidas en `App\Enums\CaseStatus::allowedTransitions()`.
- Cambios automáticos, siempre registrados en el historial: *En revisión → Asignado* al asignar el primer responsable; *Asignado → En seguimiento* al registrar el primer seguimiento; *→ Derivado* al registrar una derivación, si la transición está permitida.
- Ninguna operación cierra un caso automáticamente.
- La prioridad inicial es *urgente* si se comunicó riesgo inmediato y *alta* si la categoría lo sugiere. El DECE puede cambiarla indicando el motivo.

## 5. Tareas programadas

`tic:recordatorios-seguimiento` se ejecuta a diario a las 07:00 (`routes/console.php`) y notifica los seguimientos con fecha vencida o del día. Para activarlo:

- Desarrollo: `php artisan schedule:work`.
- Linux: cron `* * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1`.
- Windows: Programador de tareas, ejecutando `php artisan schedule:run` cada minuto en la carpeta del proyecto.

## 6. Pruebas

```bash
composer test
```

Usan la base `tic_ues_test`, que se recrea en cada ejecución. El resultado verificado está en `docs/RESULTADOS-PRUEBAS.md`.

## 7. Copias de seguridad

```bash
# Respaldo diario (base de datos + evidencias)
pg_dump -U tic_ues -h 127.0.0.1 -Fc tic_ues > respaldo_$(date +%F).dump
tar czf evidencias_$(date +%F).tgz storage/app/private/evidencias

# Restauración en una base vacía
pg_restore -U tic_ues -h 127.0.0.1 -d tic_ues --clean respaldo_AAAA-MM-DD.dump
tar xzf evidencias_AAAA-MM-DD.tgz
```

Guarde los respaldos cifrados y fuera del servidor, y pruebe la restauración periódicamente. En Windows use `pg_dump.exe` desde `C:\Program Files\PostgreSQL\17\bin`.

## 8. Limitaciones conocidas y pendientes institucionales

- Si una operación falla después de guardar un archivo adjunto, el archivo queda en disco sin registro asociado. No queda expuesto, porque el disco es privado, pero ocupa espacio.
- El envío de correos depende de configurar SMTP en `.env`. Con `MAIL_MAILER=log` los mensajes solo se escriben en `storage/logs/laravel.log`.
- La validación de contraseñas no consulta bases de filtraciones (`uncompromised`), para no depender de un servicio externo.
- Requieren decisión institucional antes de usar datos reales: el texto del aviso de privacidad, los plazos de conservación, el registro de la IP en la bitácora y la evaluación de cumplimiento de la LOPDP (ver `docs/FASE-0-ARQUITECTURA.md`, sección 8).
