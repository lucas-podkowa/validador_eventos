# Diseño: Plantillas por tipo y preselección

Fecha: 2026-05-27

Resumen

- Añadir clasificación por `tipo` a `plantilla_certificado` (asistencia, aprobacion, disertante, colaborador).
- Añadir indicador `por_defecto` para preseleccionar plantillas por `categoria + tipo`.
- Actualizar Admin/Categorias para clasificar plantillas y marcar predeterminadas.
- Actualizar EmisorCertificados para preseleccionar la plantilla adecuada según `rol` y `por_aprobacion`.

Cambios implementados

- Migración: `database/migrations/2026_05_27_000001_add_tipo_por_defecto_to_plantilla_certificado.php` — añade `tipo` y `por_defecto` y ejecuta una migración de datos mínima (marcar `tipo = 'asistencia'` para plantillas existentes y `por_defecto = true` para la primera plantilla por categoría).
- Modelo: `app/Models/PlantillaCertificado.php` — añade `TIPOS`, `$fillable` con nuevos campos y scopes `tipo`/`por_defecto`.
- Admin (Livewire): `app/Livewire/Admin/Categorias.php` — formulario de subida ahora incluye `tipo` y `por_defecto`; al subir una plantilla marcada por defecto, desmarca las otras del mismo `categoria+tipo`; al borrar plantilla por defecto, intenta reasignar otra como defecto.
- Vistas: `resources/views/livewire/admin/categorias.blade.php` — formulario actualizado; listado muestra `tipo` y etiqueta "Predet." para plantillas por defecto.
- Emisión (Livewire): `app/Livewire/EmisorCertificados.php` — agrupa plantillas por `tipo`, determina el tipo por defecto según `rol` y `por_aprobacion`, preselecciona la plantilla `por_defecto` y valida que la plantilla pertenece a la categoría y tipo seleccionados.
- Vistas: `resources/views/livewire/emisor-certificados.blade.php` — añade selector `Tipo de certificado` (cuando corresponde) y muestra sólo las plantillas del tipo seleccionado; fallback a carga manual si no hay plantillas para ese tipo.

Pasos para validar localmente

1. Asegúrate de que MySQL esté en marcha y la conexión en `.env` sea correcta.
2. Ejecutar migraciones:

```bash
php artisan migrate
```

3. Abrir `/admin/categorias` y revisar plantillas: reclasificar si necesario y marcar predeterminadas.
4. Abrir la interfaz de emisión (`EmisorCertificados`), seleccionar un evento finalizado y un rol (Participante/Disertante/Colaborador). Verificar que:
    - Si el evento es por aprobación, el tipo `Aprobación` se propone por defecto para participantes (si existe la plantilla), sino `Asistencia`.
    - Para Disertante/Colaborador se propone su tipo correspondiente.
    - Si no existe plantilla para el tipo seleccionado, aparece el input de carga obligatoria.

Notas y consideraciones futuras

- Podemos añadir un script de sugerencias para reclasificar plantillas antiguas según nombre.
- Se puede mejorar la UI para mostrar mejor la relación `categoria + tipo` en una tabla.
