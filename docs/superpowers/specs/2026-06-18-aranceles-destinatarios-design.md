# Especificación: Aranceles por Destinatario en Eventos

## 1. Resumen

Se agrega la posibilidad de configurar eventos **arancelados**, donde el monto a pagar depende del **destinatario** (rol/relación del inscripto con la institución). El administrador puede gestionar el catálogo de destinatarios, indicar si un evento es arancelado, cargar el link de pago externo y definir el precio por cada destinatario habilitado. En el formulario público, el aspirante elige su destinatario y, si corresponde abonar, ve el link de pago y debe subir el comprobante.

## 2. Alcance

- ABM de destinatarios (solo administrador).
- Configuración de arancel en la creación/edición de eventos.
- Formulario público condicional (selector de destinatario, link de pago, comprobante).
- Almacenamiento del comprobante en disco privado.
- Visualización/descarga del comprobante desde el listado de inscriptos.
- Tests Feature de los flujos nuevos.

## 3. Modelo de datos

### 3.1 `destinatarios`

| Columna | Tipo | Notas |
|---|---|---|
| `destinatario_id` | `bigint unsigned` PK |  |
| `nombre` | `varchar(255)` | Único. |
| `activo` | `boolean` | Default `true`. |

### 3.2 `evento_destinatario` (pivote)

| Columna | Tipo | Notas |
|---|---|---|
| `evento_id` | `uuid` | FK → `evento.evento_id`, cascade. |
| `destinatario_id` | `bigint unsigned` | FK → `destinatarios.destinatario_id`, cascade. |
| `precio` | `decimal(10,2)` | ≥ 0. |
| PK | `evento_id, destinatario_id` |  |

### 3.3 `evento`

- `arancel` boolean default `false`.
- `link_pago` varchar(500) nullable.

### 3.4 `inscripcion_participante`

- `destinatario_id` nullable FK → `destinatarios`, restrict.
- `monto` decimal(10,2) nullable (precio congelado al inscribirse).
- `comprobante_pago` string nullable (ruta en disco `private`).

### 3.5 Relaciones Eloquent

```php
// Evento
public function destinatarios()
{
    return $this->belongsToMany(Destinatario::class, 'evento_destinatario', 'evento_id', 'destinatario_id')
        ->withPivot('precio');
}

// InscripcionParticipante
public function destinatario()
{
    return $this->belongsTo(Destinatario::class, 'destinatario_id', 'destinatario_id');
}
```

### 3.6 Destinatarios iniciales

1. Docente / No Docente de la Facultad de Ingeniería
2. Estudiante de la Facultad de Ingeniería
3. Graduado de la Facultad de Ingeniería
4. Docente / No Docente de la UNaM
5. Estudiante de la UNaM
6. Graduado de la UNaM
7. Profesional Externo
8. Público General

Se cargan de forma segura mediante una migración con `insertOrIgnore` y también en `DatabaseSeeder` con `firstOrCreate`.

## 4. ABM de Destinatarios

- Componente: `App\Livewire\Admin\Destinatarios`
- Vista: `resources/views/livewire/admin/destinatarios.blade.php`
- Ruta: `/admin/destinatarios`, protegida por `can:crear_eventos`.
- Funciones: listado paginado, crear, editar, activar/desactivar y eliminar.
- No se permite eliminar un destinatario asociado a algún evento.

## 5. Formulario de evento (`CrearEvento`)

- Checkbox **“Evento arancelado”**.
- Si está marcado:
  - Campo **Link de pago**, requerido si al menos un destinatario tiene precio > 0.
  - Listado de destinatarios con checkbox y campo de precio.
- Validaciones:
  - Al menos un destinatario seleccionado cuando es arancelado.
  - Precios numéricos ≥ 0.
  - No se permite desmarcar un destinatario que ya tenga inscripciones asociadas.
  - No se permite quitar el arancel si ya existen inscripciones con destinatarios.
- Persistencia: se sincroniza la tabla pivote `evento_destinatario` con los precios.

## 6. Formulario público de inscripción (`RegistroEventoPublico`)

- Si el evento es arancelado se muestra un selector con los destinatarios habilitados.
- Al seleccionar un destinatario se calcula el monto desde el pivote.
- Si el monto es > 0:
  - Se muestra el monto y el link de pago externo.
  - Se exige subir un comprobante (PDF, JPG o PNG, máx. 2 MB) en disco privado.
- Si el monto es $0 o el evento no es arancelado, no se muestra link ni comprobante.
- Se guardan `destinatario_id`, `monto` y la ruta del comprobante en `inscripcion_participante`.

## 7. Backoffice: listado de inscriptos

- En la tabla de inscriptos de `EventosActivos` se agregan columnas:
  - **Destinatario**
  - **Monto**
  - **Comprobante** (icono para descargar si existe)
- Ruta de descarga: `/comprobante/{inscripcion}` → `ComprobantePagoController@show`, autenticada.
- El CSV de exportación también incluye destinatario y monto.

## 8. Migraciones

- `2026_06_18_152350_create_destinatarios_table.php`
- `2026_06_18_152350_create_evento_destinatario_table.php`
- `2026_06_18_152351_add_arancel_and_link_pago_to_evento_table.php`
- `2026_06_18_152351_add_pago_fields_to_inscripcion_participante_table.php`

## 9. Tests

Archivo: `tests/Feature/ArancelesDestinatariosTest.php`

- Crear destinatario.
- No eliminar destinatario en uso.
- Crear evento arancelado con precios.
- Validar link de pago requerido cuando hay precio positivo.
- Evento gratuito no pide destinatario ni comprobante.
- Evento arancelado exige comprobante cuando el precio es > 0.
- Destinatario con precio $0 no exige comprobante.
- Descarga del comprobante por un administrador.

## 10. Archivos afectados

Nuevos:
- `app/Models/Destinatario.php`
- `app/Livewire/Admin/Destinatarios.php`
- `resources/views/livewire/admin/destinatarios.blade.php`
- `app/Http/Controllers/ComprobantePagoController.php`
- `tests/Feature/ArancelesDestinatariosTest.php`
- 4 migraciones nuevas.

Modificados:
- `app/Models/Evento.php`
- `app/Models/InscripcionParticipante.php`
- `app/Livewire/CrearEvento.php`
- `resources/views/livewire/crear-evento.blade.php`
- `app/Livewire/RegistroEventoPublico.php`
- `resources/views/livewire/registro-evento-publico.blade.php`
- `app/Livewire/EventosActivos.php`
- `resources/views/livewire/eventos-activos.blade.php`
- `routes/web.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/navigation-menu.blade.php`
