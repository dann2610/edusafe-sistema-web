# Auditoría técnica y plan de trabajo (rediseño integral)

**Fecha:** 23 de octubre de 2026 · **Base auditada:** commit `25f0dbe` (sistema de una sola institución, 111 pruebas en verde)

## 1. Estado encontrado

| Área | Situación | Hallazgo |
|---|---|---|
| Stack | Laravel 12, PHP 8.3, PostgreSQL 16, Blade + Bootstrap 5.3 local, Chart.js | Adecuado; se conserva |
| Autenticación | Login con bloqueo, recuperación, cambio obligatorio, sesiones cifradas | Correcto; se conserva |
| Roles | Administrador técnico, Docente, DECE, Autoridad, Policía | **Falta el superadministrador de plataforma**; el administrador no está acotado a una institución |
| Modelo de datos | 25 tablas con CHECK, FK RESTRICT y triggers de inmutabilidad | **No existe la entidad «institución»**: todos los datos son de una sola escuela |
| Aislamiento | Policies por rol y por caso | **Al agregar instituciones habría fugas**: 13 consultas a `User` y 14 reglas `Rule::exists` no filtran por institución (asignables, tutores, responsables, notificaciones al DECE, validación de estudiantes y cursos) |
| Categorías | CRUD genérico con prioridad alta sí/no | No hay categorías globales vs. locales, ni campos adicionales, ni responsable inicial, ni historial visible de cambios |
| Módulos | No existen | Todo está siempre activo; no hay forma de habilitar o deshabilitar funciones por institución |
| Página pública | Funcional, con aviso de emergencia y privacidad | Diseño correcto pero genérico; sin ilustración ni explicación por pasos |
| Formulario público | Un solo bloque largo | Sin hora desconocida, «sigue ocurriendo», «ya se informó a una autoridad», personas involucradas, modalidad con o sin contacto, evidencias ni paso de revisión |
| Confirmación | Código del reporte | Falta la fecha de recepción, los próximos pasos y la advertencia de que el envío no implica verificación |
| Consulta de estado | No existe | — |
| Panel docente | Tarjetas básicas | No distingue «mis reportes» de «asignados»; el docente no puede agregar información a su reporte ni escalar un caso al DECE |
| Flujos | Transiciones fijas | No se pueden configurar los requisitos para cerrar un caso |
| Confirmaciones | `window.confirm` del navegador | Poco accesible y fuera del estilo del sistema; se reemplaza por diálogos propios |
| Tipografía | Fuente del sistema | Se adopta Inter, servida localmente |

No encontré botones sin acción, rutas rotas ni datos simulados en pantalla: todos los indicadores salen de PostgreSQL y los datos de demostración están marcados con `[DEMO]`.

## 2. Plan por prioridades

1. **Arquitectura multiinstitucional** (base de todo lo demás): tabla `institutions`, `institution_id` en todas las tablas de negocio con migración que **conserva los datos existentes** asignándolos a la institución inicial, alcance global (*global scope*) por institución, filtros en todas las consultas y validaciones, y pruebas de acceso cruzado.
2. **Superadministrador**: registro de instituciones con su administrador inicial, estados (en preparación, activa, suspendida, inactiva), módulos, catálogos globales, estadísticas agregadas y bitácora.
3. **Módulos reales**: catálogo, autorización por la plataforma, activación por la institución y dependencias, con efecto en rutas, menú y servidor.
4. **Categorías configurables**: globales o locales, prioridad inicial, responsable inicial, campos adicionales tipados y validados en el servidor, con historial de cambios.
5. **Página pública y formulario por pasos** (A–E), con evidencias opcionales por módulo, modalidad sin contacto configurable, revisión, confirmación ampliada y consulta de estado con clave.
6. **Panel docente** con indicadores separados, información adicional y escalamiento al DECE.
7. **Requisitos de cierre configurables** por institución.
8. **Rediseño visual**: Inter, sistema de tokens, diálogos de confirmación accesibles y estados vacíos.
9. Pruebas, revisión visual, documentación y paquete final.

## 3. Información que no está disponible

- **Sección 9 en adelante del prompt** (el archivo llega cortado en «9.1 Dashboard»). El panel del DECE ya existe y se conserva y mejora.
- **Integraciones con el Ministerio de Educación, la Policía Nacional y ECU 911**: no existen. Se registra como módulo «no implementado» que no puede activarse.
- **Logotipo y datos oficiales de cada institución**: se usan el nombre y las siglas configurados, sin logotipos oficiales.

## 4. Estado al cierre de esta etapa

| Prioridad | Estado | Verificación |
|---|---|---|
| 1. Multiinstitución | Hecho. Migración no destructiva probada sobre los datos existentes (28 casos y 179 registros de bitácora conservados; reversión probada) | `MultiInstitutionTest`: casos, adjuntos, usuarios, estudiantes, categorías y conteos aislados; manipulación de URL devuelve 403/404 |
| 2. Superadministrador | Hecho: instituciones (crear, editar, estados sin borrar), administradores con contraseña temporal, módulos, catálogos globales, estadísticas agregadas, bitácora | Pruebas de registro sin duplicados, suspensión que cierra el acceso y la página pública, y de que no abre expedientes |
| 3. Módulos | Hecho: plataforma → autorización → activación por institución, con dependencias; afecta rutas (404), menú y pestañas del expediente. «Integración externa» figura como no implementado y no puede activarse | Pruebas de rutas bloqueadas, dependencias y módulo no implementado |
| 4. Categorías | Hecho: globales y locales, prioridad y responsable inicial, campos tipados (texto, texto largo, fecha, hora, lista, casilla), historial con valores anterior y nuevo; no se eliminan | Pruebas de validación de tipos, opciones y solo lectura de las globales |
| 5. Página pública y formulario | Hecho: inicio rediseñado, asistente A–E con progreso y revisión (funciona también sin JavaScript), evidencias solo con el módulo, modalidad sin contacto según la institución, confirmación ampliada y consulta de estado | `PublicReportTest` (15 pruebas) y recorrido con navegador en escritorio y móvil |
| 6. Panel docente | Hecho: indicadores separados de reportes creados y asignados, información adicional y escalamiento al DECE | Prueba de notas y escalamiento |
| 7. Requisitos de cierre | Hecho: exigir actuación registrada y medidas resueltas, por institución | Prueba de cierre bloqueado y permitido |
| 8. Rediseño visual | Hecho: Inter local, tokens de color con significado, diálogo de confirmación propio, estados vacíos, foco visible | Revisión visual de todas las pantallas por rol |

**No se implementó** un editor de flujos de estados libre (estados y transiciones a medida por institución): cambiarlos rompería la comparabilidad de los indicadores y las reglas de inmutabilidad. Lo configurable son los requisitos de cierre y el responsable inicial por categoría. El campo de tipo «archivo» dentro de las categorías tampoco se habilitó; las evidencias se manejan con el módulo de adjuntos privados.
