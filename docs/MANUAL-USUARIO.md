# Manual básico de usuario

**TIC para Unidades Educativas Seguras** — Unidad Educativa Cardenal Richard Cushing

El sistema ayuda a registrar y dar seguimiento a situaciones de convivencia y seguridad escolar. **No atiende emergencias:** ante un peligro inmediato llame al **ECU 911** y active el protocolo institucional.

## 0. Plataforma e instituciones

La plataforma puede alojar varias instituciones. Cada una ve **solo sus propios datos**. El **superadministrador** registra instituciones (con su administrador inicial), autoriza módulos, mantiene los catálogos globales y consulta cifras agregadas; no abre expedientes. El **administrador institucional** gestiona usuarios, categorías propias, catálogos, la configuración de su institución (contacto, reportes sin datos de contacto, límite por hora, requisitos de cierre) y activa los módulos autorizados en **Mi institución**.

## 1. Ingreso

1. Abra la dirección del sistema y pulse **Iniciar sesión**.
2. Escriba su correo institucional y su contraseña.
3. La primera vez el sistema le pedirá cambiar la contraseña temporal. La nueva debe tener al menos 12 caracteres, con mayúsculas, minúsculas, números y símbolos.
4. Si olvidó la contraseña, use **¿Olvidó su contraseña?** y siga el enlace que llegará a su correo.

Después de 5 intentos fallidos el acceso se bloquea 5 minutos. Cierre siempre la sesión en equipos compartidos (menú con sus iniciales → **Cerrar sesión**).

## 2. Lo que puede hacer cada perfil

| Perfil | Funciones |
|---|---|
| **Docente o tutor** | Registrar incidentes, consultar el estado de sus reportes y registrar seguimientos de los casos que le asignen |
| **DECE** | Todo lo anterior, más: bandeja de casos, cambio de estado y prioridad, asignación de responsables, medidas, derivaciones, dashboard y reportes, incluido el listado confidencial |
| **Autoridad institucional** | Registrar incidentes, dashboard y reportes agregados (sin datos personales), consulta de la bitácora |
| **Personal policial** | Consultar solo los casos derivados a su entidad, con la información que la institución decidió compartir |
| **Administrador técnico** | Usuarios, catálogos, código QR y bitácora. No accede a los expedientes |

La campana de la barra superior muestra las notificaciones nuevas: reportes recibidos, asignaciones, cambios de estado y seguimientos pendientes.

## 3. Reporte público (código QR)

Cada institución tiene su página: `/i/{siglas}` (la inicial también responde en `/`). El formulario tiene pasos: **qué pasó** (tipo, fecha y hora aproximadas o desconocidas, lugar, descripción, si continúa, riesgo inmediato, si ya se informó a una autoridad), **personas** (sin nombres obligatorios), **sobre ti** (con o sin contacto, si la institución lo permite), **evidencias** (solo si el módulo está activo) y **revisión**. Al enviar se muestra el código, la fecha y, si la consulta de estado está activa, una **clave** que solo se ve una vez y permite consultar el estado en «Consultar un reporte».


Al escanear el QR se abre la página pública. **Reportar un incidente** abre un formulario que no requiere cuenta. Al enviar, la persona recibe un código de reporte. El canal no es anónimo y no admite archivos adjuntos.

## 4. Registrar un incidente (personal)

**Registrar incidente** en el menú lateral:

1. **Datos del hecho:** categoría (obligatoria), curso, fecha y lugar aproximados, y descripción objetiva de lo observado o comunicado. Marque *riesgo inmediato* solo si se comunicó un peligro actual.
2. **Personas involucradas** (solo si es necesario): pulse **Agregar** y elija un estudiante o describa a la persona de forma general. El rol «señalado» no implica responsabilidad.
3. **Evidencias** (opcional): hasta 3 archivos PDF, JPG, PNG o DOCX. No adjunte imágenes íntimas de menores.

Al guardar se genera un código (por ejemplo `UECRC-2026-000123`) y el DECE es notificado. En **Mis reportes** verá el estado de atención; por confidencialidad, las actuaciones solo las ven el DECE y los responsables.

## 4.1 Docentes: reportes y casos asignados

El inicio separa **Reportes que registré** (pendientes, información solicitada, en atención, cerrados) de **Casos asignados a mí** (abiertos, que requieren actuación, seguimientos vencidos). En un reporte propio abierto puede **agregar información** para el DECE; en un caso asignado puede **escalar al DECE** con un motivo. No ve expedientes que no le correspondan.

## 5. Gestión de casos (DECE)

**Bandeja de casos:** los urgentes aparecen primero, marcados en rojo. Filtre por código, estado, categoría, curso, responsable, prioridad o fechas.

Dentro de un caso:

- **Resumen:** datos del reporte, involucrados, evidencias, cambio de estado, prioridad y responsables.
- **Estados:** Registrado → En revisión → Asignado → En seguimiento → Derivado / Cerrado. Pedir información, derivar, cerrar o reabrir exige una justificación; cerrar exige además un resumen de cierre. Asignar o derivar nunca cierra el caso.
- **Asignar responsable:** el caso pasa a *Asignado* y la persona recibe una notificación.
- **Seguimientos:** registre cada actuación con su fecha, descripción, próxima acción y fecha prevista. El primer seguimiento pasa el caso a *En seguimiento*. Los seguimientos no se editan: use **Registrar una corrección**, que conserva el original.
- **Medidas:** formativas, preventivas o de protección, con responsable, fechas y estado de cumplimiento.
- **Derivaciones:** documente el oficio enviado por el canal oficial. El campo *Información compartida con la entidad* es lo único que verá el personal policial.
- **Historial:** cronología completa e inalterable del caso.

## 6. Indicadores y reportes

**Dashboard:** totales, casos por estado, categoría y prioridad, y evolución mensual, con filtros por periodo, categoría, estado y fechas.

**Reportes:** vista previa y descarga en **PDF** o **Excel**, con datos agregados. El DECE puede descargar además el **listado de casos confidencial**; cada descarga queda registrada.

## 7. Administración técnica

- **Usuarios:** crear cuentas (se muestra una contraseña temporal una sola vez), editar, cambiar el rol, desactivar con motivo o generar una nueva contraseña temporal. Las cuentas policiales deben vincularse a una entidad.
- **Catálogos:** periodos lectivos (solo uno vigente), cursos, estudiantes (también por importación CSV), categorías, entidades externas, tipos de medida y tipos de actuación. Los registros no se eliminan: se desactivan.
- **Código QR:** vista previa y descarga en PNG o SVG.
- **Bitácora:** consulta de las operaciones registradas.
