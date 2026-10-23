# Resultados de pruebas automatizadas

Ejecutadas el 22/10/2026 con PHP 8.3.6 y PostgreSQL 16 mediante `composer test` (equivalente a `php vendor/bin/phpunit --testdox`).

```
PHPUnit 11.5.57 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.6
Configuration: /home/claude/tic-ues/phpunit.xml

...............................................................  63 / 111 ( 56%)
................................................                111 / 111 (100%)

Time: 00:07.394, Memory: 66.50 MB

Audit Logger Sanitize (Tests\Unit\AuditLoggerSanitize)
 ✔ Elimina claves sensibles incluso anidadas
 ✔ Recorta textos largos

Authentication (Tests\Feature\Sprint1\Authentication)
 ✔ La pantalla de login se muestra en espanol
 ✔ Un usuario activo inicia sesion y queda registrado
 ✔ La sesion se regenera al iniciar sesion
 ✔ Credenciales incorrectas no inician sesion y se auditan
 ✔ Un correo inexistente da el mismo mensaje generico
 ✔ Una cuenta desactivada no puede iniciar sesion
 ✔ Bloqueo tras intentos fallidos repetidos
 ✔ Cerrar sesion invalida la sesion y se audita
 ✔ Cerrar sesion exige post con token csrf
 ✔ Un usuario desactivado con sesion abierta es expulsado
 ✔ Las rutas privadas exigen sesion
 ✔ Un usuario autenticado no ve el login
 ✔ Las respuestas incluyen cabeceras de seguridad
 ✔ La bitacora no guarda la contrasena intentada

Authorization (Tests\Feature\Sprint1\Authorization)
 ✔ Solo el administrador tecnico gestiona usuarios with data set "docente"
 ✔ Solo el administrador tecnico gestiona usuarios with data set "dece"
 ✔ Solo el administrador tecnico gestiona usuarios with data set "autoridad"
 ✔ Solo el administrador tecnico gestiona usuarios with data set "policia"
 ✔ El acceso denegado queda en la bitacora
 ✔ El menu solo muestra lo autorizado
 ✔ Un usuario sin rol es expulsado
 ✔ Cada rol tiene exactamente los permisos del catalogo

Case Management (Tests\Feature\Modules\CaseManagement)
 ✔ La bandeja prioriza urgentes y filtra
 ✔ Las transiciones respetan las reglas de negocio
 ✔ Asignar notifica cambia a asignado y no cierra el caso
 ✔ El responsable asignado ve el caso y registra seguimientos pero no gestiona
 ✔ Un responsable liberado pierde el acceso y queda el historial
 ✔ Las correcciones de seguimiento conservan el original
 ✔ Medidas se registran y actualizan
 ✔ La derivacion pasa a derivado sin cerrar y la policia solo ve su entidad
 ✔ Roles sin acceso a expedientes
 ✔ El expediente registra la consulta y muestra el historial
 ✔ Quien reporto recibe aviso del cambio de estado
 ✔ Notificaciones solo se marcan leidas por accion del usuario
 ✔ Recordatorio de seguimientos pendientes
 ✔ No se registran actuaciones en casos cerrados

Case Status (Tests\Unit\CaseStatus)
 ✔ Un caso registrado solo puede pasar a revision
 ✔ Asignar o derivar no cierra el caso automaticamente
 ✔ Un caso cerrado solo se reabre a revision con justificacion
 ✔ El cierre y la derivacion exigen justificacion
 ✔ Ningun estado transiciona a si mismo y todos tienen salida
 ✔ Todos los estados son alcanzables desde registrado

Catalog And Qr (Tests\Feature\Modules\CatalogAndQr)
 ✔ El administrador gestiona categorias sin eliminarlas
 ✔ Los catalogos estan restringidos al administrador
 ✔ Periodo vigente unico y cursos sin duplicados
 ✔ Registro e importacion atomica de estudiantes
 ✔ Qr sin url configurada no se genera
 ✔ Qr apunta a la url publica configurada

Create Admin Command (Tests\Feature\Fase0\CreateAdminCommand)
 ✔ Crea el administrador con contrasena segura y hasheada
 ✔ Rechaza contrasenas debiles
 ✔ Rechaza correo duplicado

Dashboard Reports (Tests\Feature\Modules\DashboardReports)
 ✔ El dashboard usa datos reales y no muestra datos personales
 ✔ El dashboard esta restringido
 ✔ Reportes pdf y excel agregados
 ✔ El listado identificable solo para dece y queda auditado
 ✔ Los textos exportados no se interpretan como formulas
 ✔ La bitacora es de solo lectura y restringida

Database Schema (Tests\Feature\Fase0\DatabaseSchema)
 ✔ La conexion usa postgresql
 ✔ Existen todas las tablas del modelo
 ✔ Los seeders son idempotentes
 ✔ El seeder de produccion no crea usuarios ni estudiantes
 ✔ El administrador tecnico no tiene acceso a expedientes
 ✔ La policia solo puede ver derivaciones de su entidad
 ✔ Un usuario inactivo no tiene permisos
 ✔ Las contrasenas se almacenan con hash
 ✔ El correo es unico sin distinguir mayusculas
 ✔ Los codigos de caso son unicos y con formato
 ✔ La base rechaza estados y prioridades invalidos
 ✔ Un reporte interno exige usuario registrador
 ✔ Un reporte publico puede registrarse sin usuario
 ✔ Las restricciones check coinciden con los enums
 ✔ La bitacora es inmutable en la aplicacion y en la base
 ✔ El historial del caso es inmutable
 ✔ No se eliminan catalogos vinculados a casos
 ✔ Un responsable no puede tener dos asignaciones activas en el mismo caso
 ✔ Una correccion de seguimiento exige motivo
 ✔ Solo existe un periodo lectivo vigente
 ✔ Los campos criticos del caso no son asignables en masa

Incident Registration (Tests\Feature\Modules\IncidentRegistration)
 ✔ Un docente registra un incidente completo en postgresql
 ✔ Valida campos obligatorios e involucrados
 ✔ Los adjuntos se guardan en disco privado y validan el contenido
 ✔ Los adjuntos solo se descargan con autorizacion
 ✔ Quien reporta ve su reporte pero no el expediente ni reportes ajenos
 ✔ Roles sin permiso no pueden registrar

Password (Tests\Feature\Sprint1\Password)
 ✔ La solicitud de recuperacion envia el enlace a cuentas activas
 ✔ La respuesta es identica para correos inexistentes y cuentas inactivas
 ✔ Restablecer con token valido cambia la contrasena
 ✔ Un token invalido no cambia la contrasena
 ✔ La contrasena temporal obliga a cambiarla antes de continuar
 ✔ El cambio exige la contrasena actual y la politica de seguridad

Public Report (Tests\Feature\Modules\PublicReport)
 ✔ La pagina publica muestra lo necesario sin iniciar sesion
 ✔ El boton reportar abre un formulario funcional
 ✔ Un reporte publico valido crea el caso y notifica al dece
 ✔ Exige aceptar privacidad y campos obligatorios
 ✔ Bloquea robots por campo trampa y envio inmediato
 ✔ Limita la cantidad de reportes por ip
 ✔ Si se deshabilita el canal publico no acepta reportes
 ✔ Solo acepta categorias habilitadas para el publico

User Management (Tests\Feature\Sprint1\UserManagement)
 ✔ El listado permite buscar y filtrar
 ✔ Crea un usuario con contrasena temporal hasheada
 ✔ Valida campos obligatorios y correo unico en espanol
 ✔ El rol policial exige entidad externa y otros roles la descartan
 ✔ No se pueden asignar campos protegidos por asignacion masiva
 ✔ Actualiza datos y rol cerrando sus sesiones
 ✔ El administrador no puede cambiar su propio rol
 ✔ Desactivar exige motivo cierra sesiones y se audita
 ✔ No puede desactivarse a si mismo ni al ultimo administrador
 ✔ Genera contrasena temporal nueva y obliga a cambiarla
 ✔ El administrador tecnico no tiene acceso a expedientes

OK (111 tests, 751 assertions)
```
