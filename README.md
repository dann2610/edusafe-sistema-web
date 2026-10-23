# TIC para Unidades Educativas Seguras

**Monitoreo, comunicación y cultura de prevención** — Unidad Educativa Cardenal Richard Cushing (Ecuador).

Sistema web para registrar, gestionar y dar seguimiento a incidentes de seguridad y convivencia escolar, con apoyo al DECE e indicadores agregados. Es una herramienta de apoyo: no determina la veracidad de una denuncia, no asigna responsabilidades y no reemplaza los protocolos oficiales ni los canales de emergencia (ECU 911).

Stack: Laravel 12 · PHP 8.2+ · PostgreSQL 14+ · Blade + Bootstrap 5 · PHPUnit 11.

Estado actual: **Fase 0 completada** (arquitectura, modelo de datos, seeders, pruebas). Ver [`docs/FASE-0-ARQUITECTURA.md`](docs/FASE-0-ARQUITECTURA.md).

## Requisitos

PHP 8.2 o superior con las extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `zip`, `gd` e `intl`; Composer 2; PostgreSQL 14 o superior; Git.

## 1. Crear la base de datos

Abra `psql` como superusuario (`psql -U postgres`) y ejecute, reemplazando la contraseña:

```sql
CREATE ROLE tic_ues LOGIN PASSWORD 'cambie-esta-clave';
CREATE DATABASE tic_ues      OWNER tic_ues ENCODING 'UTF8' TEMPLATE template0;
CREATE DATABASE tic_ues_test OWNER tic_ues ENCODING 'UTF8' TEMPLATE template0;
```

`tic_ues_test` la usan exclusivamente las pruebas automatizadas, que la vacían en cada ejecución.

## 2. Instalar el proyecto

```bash
git clone <url-del-repositorio> tic-ues
cd tic-ues
composer install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
```

Edite `.env` y complete al menos `DB_PASSWORD` con la clave del paso 1. Para datos de demostración puede fijar `DEMO_PASSWORD`; si lo deja vacío, se generará una contraseña aleatoria por usuario y se mostrará una sola vez.

## 3. Migrar y poblar

```bash
php artisan migrate --seed
```

En `APP_ENV=local` esto crea roles, permisos, catálogos y datos DEMO ficticios (usuarios `*.demo@uecrc.test`, estudiantes `DEMO-0001…`). En producción solo crea roles, permisos y catálogos.

## 4. Crear el administrador inicial (producción)

```bash
php artisan tic:crear-admin --nombre="Nombre Apellido" --email=soporte@institucion.edu.ec
```

La contraseña se pide de forma oculta y debe tener al menos 12 caracteres con mayúsculas, minúsculas, números y símbolos.

## 5. Ejecutar

```bash
php artisan serve             # http://localhost:8000
```

`localhost` solo es accesible desde su propio equipo. El código QR definitivo deberá apuntar a una URL pública HTTPS (`QR_URL_PUBLICA`).

## 6. Pruebas

```bash
composer test                 # o: php vendor/bin/phpunit --testdox
```

## Seguridad

Nunca suba `.env` al repositorio. No cargue datos reales de estudiantes sin la autorización institucional descrita en la sección 8 del documento de arquitectura. En producción use `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` y HTTPS.
