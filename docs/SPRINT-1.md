# Sprint 1 — Autenticación, usuarios, permisos y bitácora

**Fecha:** 22 de octubre de 2026 · **Estado:** terminado y verificado

## Qué se construyó

| Funcionalidad | Detalle |
|---|---|
| Inicio de sesión | Correo y contraseña; mensaje genérico ante credenciales incorrectas; sin "recordarme" (equipos compartidos) |
| Bloqueo por intentos | 5 intentos fallidos por correo e IP bloquean 5 minutos (`LOGIN_MAX_INTENTOS`); límite adicional de 20 envíos por minuto |
| Cierre de sesión | Solo por POST con token CSRF; invalida la sesión |
| Recuperación de contraseña | Enlace por correo con vencimiento; misma respuesta exista o no el correo; solo cuentas activas |
| Contraseña temporal | Las cuentas nuevas reciben una clave aleatoria que se muestra una sola vez y debe cambiarse al ingresar |
| Cambio de contraseña | Exige la actual; mínimo 12 caracteres con mayúsculas, minúsculas, números y símbolos; cierra otras sesiones |
| Cuentas desactivadas | No pueden ingresar y son expulsadas aunque tengan una sesión abierta |
| Gestión de usuarios | Listado con búsqueda, filtros por rol y estado y paginación; crear, editar, activar, desactivar con motivo, generar clave temporal |
| Reglas de protección | Nadie se desactiva ni se cambia el rol a sí mismo; el último administrador activo no puede desactivarse ni perder su rol |
| Cuentas policiales | Obligatoriamente vinculadas a una entidad policial (Policía Nacional o DINAPEN) |
| Permisos | Una Gate por permiso desde `App\Support\PermissionCatalog`; `UserPolicy`; menú que solo muestra lo autorizado |
| Bitácora | Registra inicios y cierres de sesión, fallos, bloqueos, accesos denegados (403), recuperación y cambios de contraseña y toda operación sobre cuentas; nunca guarda contraseñas ni tokens |
| Seguridad HTTP | CSP restrictiva, X-Frame-Options, nosniff, Referrer-Policy, HSTS bajo HTTPS, sin caché en páginas privadas |
| Interfaz | Bootstrap 5.3 e iconos servidos localmente (sin CDN); menú lateral, barra superior, diseño adaptable, foco visible, enlace "Saltar al contenido", errores accesibles |
| Idioma | Validación, autenticación, paginación y páginas de error (403, 404, 419, 429, 500, 503) en español |

## Rutas

| Método | URL | Acceso |
|---|---|---|
| GET/POST | `/login` | Visitantes |
| GET/POST | `/recuperar-contrasena` | Visitantes |
| GET | `/restablecer-contrasena/{token}` · POST `/restablecer-contrasena` | Visitantes |
| POST | `/logout` | Sesión iniciada |
| GET/PUT | `/cambiar-contrasena` | Sesión iniciada |
| GET | `/panel` · `/perfil` | Sesión iniciada |
| GET/POST/PUT/PATCH | `/admin/usuarios…` | Permiso `usuarios.gestionar` |

La raíz `/` redirige a `/login` hasta que el Sprint 3 incorpore la página pública.

## Verificación

Pruebas automatizadas: **71 pruebas, 470 aserciones, todas en verde** (PHP 8.3, PostgreSQL 16). Comprueban, entre otras cosas, que cada rol (docente, DECE, autoridad, policía) recibe 403 al intentar ver, crear, editar, desactivar o regenerar contraseñas de usuarios escribiendo la URL directamente, y que ninguna contraseña aparece en la bitácora.

Revisión visual en navegador (computadora 1366 px y celular 390 px). Detectó y corrigió tres defectos que las pruebas no cubren: el menú lateral ilegible en pantallas grandes, el desbordamiento horizontal de la tabla en celulares y la vinculación posible de un policía a una entidad no policial.

## Prueba manual sugerida

1. `php artisan serve` y abrir `http://localhost:8000`.
2. Ingresar con `admin.demo@uecrc.test` y la contraseña `DEMO_PASSWORD` del `.env`.
3. En **Usuarios → Nuevo usuario**, crear una cuenta y copiar la contraseña temporal.
4. Cerrar sesión, ingresar con la nueva cuenta y comprobar que obliga a cambiar la contraseña.
5. Ingresar como `docente.demo@uecrc.test` y abrir `http://localhost:8000/admin/usuarios`: debe mostrar **403 Acceso denegado**.
6. Escribir 5 veces una contraseña incorrecta: el sexto intento queda bloqueado.

Los correos de recuperación no se envían mientras `MAIL_MAILER=log`: el enlace se escribe en `storage/logs/laravel.log`.

## Pendiente (Sprint 2)

Catálogos institucionales: periodos lectivos, cursos, estudiantes con matrícula, categorías de incidentes, entidades externas, tipos de medida y tipos de actuación, con creación, edición, desactivación y bloqueo de eliminación cuando hay historial.
