# TIC para Unidades Educativas Seguras

**Monitoreo, comunicación y cultura de prevención** — plataforma multiinstitucional. La institución inicial es la Unidad Educativa Cardenal Richard Cushing (Ecuador).

Sistema web para registrar, gestionar y dar seguimiento a incidentes de seguridad y convivencia escolar, con apoyo al DECE e indicadores agregados. Es una herramienta de apoyo: no determina la veracidad de una denuncia, no asigna responsabilidades y no reemplaza los protocolos oficiales ni los canales de emergencia (ECU 911).

Stack: Laravel 12 · PHP 8.2+ · PostgreSQL 14+ · Blade + Bootstrap 5 · Chart.js · PHPUnit 11.

## Módulos

Plataforma multiinstitucional con superadministrador (instituciones, módulos, catálogos globales, estadísticas agregadas) y aislamiento de datos por institución · Módulos activables por institución con dependencias · Categorías globales y locales con campos adicionales tipados, prioridad y responsable inicial · Página pública por institución y código QR · Formulario público por pasos (incidente, personas, quien reporta, evidencias opcionales, revisión) con consulta de estado por código y clave · Reporte público con protección antispam · Autenticación y recuperación de contraseña · Usuarios, roles y permisos · Catálogos (periodos, cursos, estudiantes, categorías, entidades, tipos de medida y de actuación) · Registro de incidentes con involucrados y evidencias privadas · Bandeja del DECE y gestión de casos · Asignaciones · Seguimientos con correcciones trazables · Medidas · Derivaciones y vista policial · Historial inmutable · Notificaciones internas · Dashboard · Reportes PDF y Excel · Bitácora de auditoría.

**Pruebas: 132 pruebas automatizadas, 996 aserciones, todas en verde** ([`docs/RESULTADOS-PRUEBAS.md`](docs/RESULTADOS-PRUEBAS.md)).

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/AUDITORIA-Y-PLAN.md`](docs/AUDITORIA-Y-PLAN.md) | Auditoría del sistema anterior, plan y estado de la segunda etapa |
| [`docs/FASE-0-ARQUITECTURA.md`](docs/FASE-0-ARQUITECTURA.md) | Arquitectura, modelo de datos, estados, permisos, decisiones y aspectos de la LOPDP |
| [`docs/MANUAL-TECNICO.md`](docs/MANUAL-TECNICO.md) | Estructura, seguridad, reglas de negocio, tareas programadas y respaldos |
| [`docs/MANUAL-USUARIO.md`](docs/MANUAL-USUARIO.md) | Uso del sistema por perfil |
| [`docs/DESPLIEGUE.md`](docs/DESPLIEGUE.md) | Producción con HTTPS y QR definitivo |
| [`docs/SPRINT-1.md`](docs/SPRINT-1.md) | Detalle del módulo de autenticación |

## Instalación local (Windows con Laragon o similar)

Requisitos: PHP 8.2+ con las extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `fileinfo`, `openssl`, `zip`, `gd` e `intl`; Composer 2; PostgreSQL 14+.

**1. Base de datos.** En pgAdmin (Query Tool) ejecute cada línea por separado:

```sql
CREATE ROLE tic_ues LOGIN PASSWORD 'MiClave2026';
CREATE DATABASE tic_ues OWNER tic_ues ENCODING 'UTF8' TEMPLATE template0;
CREATE DATABASE tic_ues_test OWNER tic_ues ENCODING 'UTF8' TEMPLATE template0;
```

**2. Proyecto.** En PowerShell, dentro de la carpeta del proyecto:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
(Get-Content .env) -replace '^DB_PASSWORD=.*', 'DB_PASSWORD=MiClave2026' -replace '^DEMO_PASSWORD=.*', 'DEMO_PASSWORD=Demo.Local-2026' | Set-Content .env
php artisan migrate:fresh --seed
php artisan serve
```

Abra **http://localhost:8000**. Usuarios de demostración (contraseña `Demo.Local-2026`):

| Correo | Perfil |
|---|---|
| `superadmin.demo@uecrc.test` | Superadministrador de la plataforma |
| `admin.demo@uecrc.test` | Administrador institucional (UECRC) |
| `dece.demo@uecrc.test` | DECE (UECRC) |
| `docente.demo@uecrc.test` | Docente o tutor (UECRC) |
| `autoridad.demo@uecrc.test` | Autoridad institucional (UECRC) |
| `policia.demo@uecrc.test` | Personal policial |
| `admin@uedemo.test`, `dece@uedemo.test`, `docente@uedemo.test` | Segunda institución ficticia (UEDEMO), para comprobar el aislamiento |

Los casos de demostración tienen el prefijo `[DEMO]` y los estudiantes son ficticios (`DEMO-0001…`, `DEMO2-…`). Página pública de la segunda institución: `http://localhost:8000/i/uedemo`.

**Actualizar una instalación existente sin perder datos:** `composer dump-autoload`, `php artisan optimize:clear` y `php artisan migrate`. La migración asigna todos los registros existentes a la institución inicial.

**3. Pruebas:** `composer test`.

`localhost` solo funciona en su propio equipo: el QR definitivo requiere publicar el sistema con HTTPS (ver `docs/DESPLIEGUE.md`).

## Seguridad

Nunca suba `.env` al repositorio. No cargue datos reales de estudiantes sin la autorización institucional (sección 8 del documento de arquitectura). En producción use `APP_DEBUG=false`, HTTPS y `SESSION_SECURE_COOKIE=true`, y cree el administrador con `php artisan tic:crear-admin`.
