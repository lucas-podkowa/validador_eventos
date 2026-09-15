# Diseño: Detección y corrección de participantes duplicados

Fecha: 2026-09-11

## Problema

El sistema identifica a un participante por `dni`. Si una persona se inscribe con un
DNI mal tipeado (difiere en uno o más dígitos) y un mail distinto (único), se crea un
participante nuevo aunque el nombre y el teléfono coincidan. En producción se detectaron
12 grupos con mismo apellido+nombre (25 registros).

## Objetivo

- Detectar posibles duplicados al crear/inscribir participantes y ofrecer vincularlos.
- Permitir que el propio participante corrija sus datos desde el portal, con validación
  de identidad mediante imagen del DNI y aprobación de un administrador.
- No abrir vectores de seguridad en formularios anónimos (no editar DNI/mail ajenos).

## Alcance de esta entrega

Fases 1 y 2. La limpieza (fusión) de los duplicados existentes queda para una fase 3.

## Fase 1 — Prevención

1. Normalización:
   - Columnas `nombre_norm`, `apellido_norm`, `telefono_norm` en `participante` (nullable, indexadas).
   - `App\Support\NormalizadorIdentidad`: nombre sin acentos/minúsculas/espacios colapsados;
     teléfono solo dígitos sin ceros iniciales; mail en minúsculas.
   - Cálculo en el evento `saving` del modelo `Participante`.
   - Comando idempotente `participantes:normalizar` (backfill).
2. `App\Actions\BuscarParticipanteSimilar`: busca por `apellido_norm + nombre_norm + telefono_norm`
   excluyendo el DNI/participante consultado.
3. Confirmación en los formularios (público, staff, emisión directa):
   - Si hay candidato con distinto DNI, se muestra alerta con los datos del existente.
   - "Es la misma persona": se usa el existente y se actualizan solo nombre/apellido/teléfono
     (DNI y mail no se tocan).
   - "Es otra persona": crea un registro nuevo y lo marca en `duplicado_revision`.
   - La importación masiva no interactiva omite la fila y la reporta.
4. Tabla `duplicado_revision` (auditoría de decisiones).

## Fase 2 — Autocorrección

1. Portal `/mis-datos` (autenticado, rol Invitado):
   - Edita nombre, apellido, teléfono y mail del participante vinculado.
   - DNI de solo lectura.
2. Solicitud de corrección de DNI: nuevo DNI + motivo + una imagen obligatoria
   (`jpg/jpeg/png/webp`, validada como imagen real, máx. 10 MB), guardada en disco
   privado `solicitudes_dni/{participante_id}`.
3. Tabla `solicitud_correccion_dni` con `imagen_path/mime/original`, estado y auditoría.
4. Admin: lista solicitudes, visor seguro de imagen (ruta autenticada), aprobar/rechazar.
   Al aprobar: valida unicidad del DNI, actualiza `participante.dni` (y `users.dni` si
   corresponde) y re-emite certificados mediante `ReemitirCertificadosParticipante`.
5. Re-emisión de certificados de eventos (y títulos) reutilizando la generación actual,
   resolviendo la plantilla desde `plantilla_certificado` por categoría y tipo.

## Seguridad

- El formulario anónimo nunca modifica DNI ni mail de un participante existente.
- Las imágenes del DNI viven en el disco privado y se sirven sólo con permiso de admin.
- SVG excluido del upload para evitar XSS.
- Toda decisión de duplicado y corrección queda auditada.

## Pruebas

- Unit: normalización y detección (caso `Becker`, teléfonos `0375…` vs `375…`).
- Feature: confirmación en público/staff, `misma_persona` vs `otra_persona`, importación
  que omite y reporta, portal edita, solicitud con imagen y aprobación que re-emite.
