# Especificación: Requisitos de Documentación por Destinatario

## 1. Resumen

Se agrega la posibilidad de que el administrador, al crear/editar un evento, defina —por cada destinatario seleccionado— una **lista dinámica de requisitos de documentación** (títulos libres, ej. "Certificado de Convivencia"). En la planilla pública de inscripción, el destinatario deberá **adjuntar un PDF por cada requisito** que aplique a su destinatario para ese evento. La cantidad y el tipo de documento es **dinámica según evento + destinatario**; puede no haber ninguno. No hay revisión posterior de los documentos: adjuntarlos completa la inscripción. Todos los requisitos definidos son obligatorios.

## 2. Alcance

- Definición de requisitos de documentación por destinatario en `CrearEvento` (admin).
- Desacoplar la asociación destinatario↔evento del flag `arancel` para que los requisitos puedan existir también en eventos gratuitos.
- Formulario público condicional: un `<input type="file">` por requisito, PDF obligatorio.
- Almacenamiento de los PDF en disco `private`.
- Visualización/descarga de los documentos desde el listado de inscriptos de `EventosActivos`.
- Guards para no romper requisitos/documentos ya presentados al editar.
- Tests Feature de los flujos nuevos.

## 3. Modelo de datos

### 3.1 `requisitos_documentacion` (nueva)

| Columna | Tipo | Notas |
|---|---|---|
| `requisito_id` | `bigint unsigned` PK | autoincrement |
| `evento_id` | `uuid` | FK → `evento.evento_id`, cascade |
| `destinatario_id` | `bigint unsigned` | FK → `destinatarios.destinatario_id`, cascade |
| `titulo` | `varchar(255)` | título libre ingresado por el admin |
| `orden` | `int unsigned` default 0 | conserva el orden del admin |
| unique | `(evento_id, destinatario_id, titulo)` | sin duplicados por destinatario |
| — | sin timestamps | convención del dominio |

### 3.2 `documentos_presentados` (nueva)

| Columna | Tipo | Notas |
|---|---|---|
| `documento_id` | `bigint unsigned` PK | autoincrement |
| `inscripcion_participante_id` | `bigint unsigned` | FK → `inscripcion_participante`, cascade |
| `requisito_id` | `bigint unsigned` | FK → `requisitos_documentacion`, **restrict** |
| `path` | `varchar` | ruta en disco `private` |
| unique | `(inscripcion_participante_id, requisito_id)` | un PDF por requisito |
| — | sin timestamps | convención |

> FK `restrict` en `requisito_id`: nunca se elimina un requisito que ya tenga documentos presentados (guard en el admin).

### 3.3 Relaciones Eloquent

```php
// Evento
public function requisitos() { return $this->hasMany(RequisitoDocumentacion::class, 'evento_id', 'evento_id'); }

// RequisitoDocumentacion
public function evento() { return $this->belongsTo(Evento::class, 'evento_id', 'evento_id'); }
public function destinatario() { return $this->belongsTo(Destinatario::class, 'destinatario_id', 'destinatario_id'); }
public function documentos() { return $this->hasMany(DocumentoPresentado::class, 'requisito_id', 'requisito_id'); }

// InscripcionParticipante
public function documentos() { return $this->hasMany(DocumentoPresentado::class, 'inscripcion_participante_id'); }

// DocumentoPresentado
public function requisito() { return $this->belongsTo(RequisitoDocumentacion::class, 'requisito_id', 'requisito_id'); }
public function inscripcion() { return $this->belongsTo(InscripcionParticipante::class, 'inscripcion_participante_id'); }
```

No se modifican `evento_destinatario` ni `inscripcion_participante`.

## 4. Admin: `CrearEvento`

- **Desacoplar destinatarios del arancel:** el bloque de destinatarios deja de estar dentro de `@if ($arancel)` y se muestra siempre. El input de **precio** se muestra/solicita solo si `arancel=true`; si no, se guarda `0` (oculto).
- **Repeater de requisitos por destinatario**, clonando el patrón de `metodosPago`: por cada destinatario tildado, inputs de texto "título" con botones **Agregar / Eliminar / Subir / Bajar**.
- Nueva propiedad `$destinatarioRequisitos = []` (`destinatario_id => [['requisito_id'=>int|null,'titulo'=>string], ...]`), con `addRequisito/removeRequisito/moveRequisitoUp/moveRequisitoDown`.
- `mount` (edición): hidratar desde `$evento->requisitos` agrupados por destinatario.
- `save()`: sincronizar destinatarios **siempre** (hoy solo si arancel). Reconciliar requisitos: mantener los que tienen `requisito_id` (update `titulo`/`orden`), insertar nuevos, eliminar quitados **solo si no tienen `documentos_presentados`**. Todo dentro de la transacción existente.
- **Validaciones:** títulos no vacíos y únicos por (destinatario, evento); no eliminar/renombrar un requisito con uploads; no desmarcar un destinatario con inscripciones (ver §9).

## 5. Pública: `RegistroEventoPublico`

- Mostrar el selector de destinatario cuando **el evento tenga destinatarios** (cambiar `@if ($evento->arancel)` → `@if ($evento->destinatarios->isNotEmpty())`).
- `updatedDestinatarioId`: además del monto, cargar `$requisitosActivos = RequisitoDocumentacion::where('evento_id', $this->evento_id)->where('destinatario_id', $value)->orderBy('orden')->get()` y resetear `$documentos = []`.
- Un `<input type="file" accept=".pdf">` por requisito, con `wire:model="documentos.{{ $requisito_id }}"` y etiqueta = título. Clonar el bloque del comprobante.
- Validación dinámica: por cada requisito activo, `"documentos.{id}" => 'required|file|mimes:pdf|max:30720'`. (Aprovechar para alinear el texto "máx. 2 MB" con la regla real.)
- En `submit`: crear la `InscripcionParticipante`; luego por cada requisito, `$file->store("documentos/{$this->evento_id}/{$inscripcion_id}", 'private')` y crear `DocumentoPresentado`. Todo en la transacción existente. El comprobante de pago queda aparte.

## 6. Backoffice: `EventosActivos`

- Agregar columna **"Documentos"** a la tabla de inscriptos: por cada `$inscripto->documentos`, un ícono/enlace `route('documento.show', $doc)` con el título del requisito (al lado del ícono de comprobante).
- Cargar relaciones `documentos.requisito` para evitar N+1.

## 7. Descarga de documentos

- Nuevo `app/Http/Controllers/DocumentoRequisitoController.php` clonando `ComprobantePagoController::show`: `abort 404` si no hay `path`/no existe en `private`, y `Storage::disk('private')->download($path)`.
- Ruta `GET /documento/{documento}` → `documento.show`, **con controller** (no closure, para no sumar restricciones a `route:cache`) y protegida por `can:ver_participantes`.

## 8. Guards y seguridad

- **Extender el guard de destinatarios** (hoy solo bloquea inscripciones **pagas**): bloquear desmarcar cualquier destinatario con inscripciones (también gratuitas, ahora que los gratuitos pueden tener destinatarios).
- **Bloquear eliminar/renombrar** un requisito que tenga `documentos_presentados` (mensaje claro).
- Archivos en disco `private` (no público), descarga autenticada por permiso existente.
- Cumple `AGENTS.md`: `migrate` (no fresh/seed en prod), ruta con controller (no closure), ninguna permission nueva (se reusa `crear_eventos` y `ver_participantes`).

## 9. Migraciones

- `2026_06_24_000001_create_requisitos_documentacion_table.php`
- `2026_06_24_000002_create_documentos_presentados_table.php`

## 10. Tests (`tests/Feature/RequisitosDocumentacionTest.php`)

- Evento gratuito con destinatarios + requisitos (sin arancel).
- Evento arancelado con requisitos + precios.
- Inscripción exige un PDF por requisito; falla si falta uno; success con todos.
- Destinatario sin requisitos no pide archivos.
- PDFs en `private`, descargables por admin; 404 si no existe.
- Guard: no eliminar requisito con uploads; no desmarcar destinatario con inscripciones (gratuito).

## 11. Archivos afectados

**Nuevos:** `app/Models/RequisitoDocumentacion.php`, `app/Models/DocumentoPresentado.php`, `app/Http/Controllers/DocumentoRequisitoController.php`, 2 migraciones, `tests/Feature/RequisitosDocumentacionTest.php`.

**Modificados:** `app/Models/Evento.php`, `app/Models/InscripcionParticipante.php`, `app/Livewire/CrearEvento.php`, `resources/views/livewire/crear-evento.blade.php`, `app/Livewire/RegistroEventoPublico.php`, `resources/views/livewire/registro-evento-publico.blade.php`, `app/Livewire/EventosActivos.php`, `resources/views/livewire/eventos-activos.blade.php`, `routes/web.php`.

## 12. Supuestos

- Requisitos estrictamente **por destinatario** (no hay "requisito global del evento"). Si un doc aplica a todos, el admin lo carga en cada destinatario.
- `ImportarParticipantes` / `InscribirStaff` (Disertantes/Colaboradores) **fuera del alcance**: no suben requisitos.
- Sin límite de cantidad de requisitos por destinatario.
- Renombrar un requisito con uploads existentes se **bloquea** (evita etiquetas engañosas).
