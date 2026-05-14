# Diseno: refactor de gestion de Indicadores

## Objetivo

Redisenar la pantalla de gestion de Indicadores para que siga el mismo nivel visual y operativo que otras pantallas administrativas del sistema, haciendo explicita la relacion entre Tipo de indicador, tipo de seleccion e indicadores asociados.

## Problema actual

La implementacion actual mezcla dos entidades relacionadas en una pantalla visualmente plana y con poco contexto.

- Los Tipos y los Indicadores no tienen una jerarquia visual clara.
- El tipo de seleccion existe a nivel de Tipo, pero no se presenta como parte central del flujo.
- El uso de modales rompe contexto y vuelve mas torpe la edicion.
- Los mensajes y acciones de eliminacion no son consistentes.
- La pantalla no mantiene el mismo lenguaje visual que vistas como Usuarios, Eventos Finalizados o Crear Evento.
- El flujo actual de alta de Tipo no deja bien resuelto el guardado del selector al crear.

## Alcance

Este refactor cubre exclusivamente la pantalla de gestion de Indicadores en Livewire y su comportamiento asociado.

Incluye:

- Nueva estructura de layout en dos columnas coordinadas.
- Jerarquia visual centrada en el Tipo activo.
- Formularios embebidos para alta y edicion de Tipos e Indicadores.
- Tabla de indicadores del Tipo seleccionado.
- Correccion de textos, confirmaciones y estados de accion.
- Ajustes minimos en el componente Livewire para soportar el nuevo flujo.

No incluye:

- Cambios de modelo de datos.
- Nuevos permisos o rutas.
- Refactor amplio de otros modulos administrativos.

## Enfoque elegido

Se adopta la direccion visual A, tipo Explorer.

Este enfoque prioriza claridad por encima de densidad. Mantiene los Tipos como unidad principal de navegacion y usa el panel derecho como superficie contextual unica para ver resumen, editar el Tipo actual y administrar sus Indicadores sin perder orientacion.

## Estructura de pantalla

### Columna izquierda: Tipos

La columna izquierda lista todos los Tipos de indicador en formato de panel navegable.

Cada item muestra:

- Nombre del Tipo.
- Tipo de seleccion en forma de etiqueta visual persistente.
- Cantidad de indicadores asociados.
- Estado activo de seleccion.

Comportamiento:

- Al hacer clic en un Tipo, ese Tipo pasa a ser el contexto activo de la pantalla.
- Si no hay un Tipo activo al entrar, se selecciona el primero disponible.
- Debe existir una accion clara para crear un nuevo Tipo.

### Columna derecha: contexto del Tipo activo

La columna derecha representa todo lo relacionado al Tipo seleccionado.

Se organiza en este orden:

1. Encabezado contextual con nombre del Tipo, selector y acciones principales.
2. Resumen breve con metricas utiles, por ejemplo cantidad de indicadores.
3. Formulario embebido para crear o editar el Tipo actual.
4. Formulario embebido para crear o editar un Indicador del Tipo activo.
5. Tabla de indicadores pertenecientes al Tipo seleccionado.

La columna derecha nunca debe mostrar datos de varios Tipos a la vez.

## Componentes funcionales

### Navegacion de Tipos

El listado izquierdo pasa a ser un selector persistente de contexto.

Requisitos:

- Resaltar claramente el Tipo activo.
- Mostrar el selector como badge legible.
- Permitir crear un nuevo Tipo sin abandonar la pantalla.
- Permitir editar o eliminar el Tipo activo desde la columna derecha.

### Formulario de Tipo

El formulario de Tipo deja de vivir en modal y pasa a estar embebido.

Campos minimos:

- Nombre.
- Selector.

Comportamiento:

- Si el usuario esta creando un Tipo, el formulario aparece limpio.
- Si esta editando el Tipo activo, el formulario aparece precargado.
- Guardar debe persistir correctamente el selector tanto en alta como en edicion.
- Cancelar debe restaurar el modo de lectura sin perder el Tipo activo.

### Formulario de Indicador

El formulario de Indicador tambien pasa a estar embebido y siempre opera sobre el Tipo activo.

Comportamiento:

- Crear un Indicador lo vincula automaticamente al Tipo seleccionado.
- Editar un Indicador reutiliza el mismo formulario con datos precargados.
- Cancelar vuelve a estado neutro sin cerrar contexto.
- Si no hay Tipo activo, el formulario de Indicador debe quedar deshabilitado o no mostrarse.

### Tabla de Indicadores

La tabla muestra unicamente los Indicadores del Tipo activo.

Debe incluir:

- Nombre del Indicador.
- Datos operativos relevantes ya existentes en el modulo.
- Acciones de editar y eliminar.
- Orden visual consistente con otras tablas administrativas del proyecto.

La tabla puede mantener ordenamiento actual si ya existe, pero el foco del refactor es visual y de flujo, no agregar nuevas capacidades de consulta.

## Flujo de interaccion

### Crear Tipo

1. Usuario activa la accion de nuevo Tipo.
2. Se habilita el formulario embebido de Tipo en modo alta.
3. Al guardar, el nuevo Tipo queda seleccionado automaticamente.
4. La tabla de Indicadores se muestra vacia para ese nuevo contexto.

### Editar Tipo

1. Usuario selecciona un Tipo en la izquierda.
2. Activa editar.
3. El formulario de Tipo se precarga en la derecha.
4. Al guardar, se refresca el panel izquierdo y el encabezado contextual.

### Crear Indicador

1. Usuario selecciona un Tipo.
2. Activa nuevo Indicador.
3. Completa el formulario embebido.
4. Al guardar, el Indicador aparece en la tabla del Tipo activo.

### Editar Indicador

1. Usuario pulsa editar desde la tabla.
2. El formulario de Indicador se precarga.
3. Al guardar, la tabla se actualiza sin perder el Tipo activo.

### Eliminar

1. Usuario activa eliminar sobre Tipo o Indicador.
2. Se muestra confirmacion con texto correcto segun la entidad.
3. Al confirmar, se elimina y se recompone el estado visible.
4. Si se elimina el Tipo activo, la pantalla selecciona otro Tipo disponible o muestra estado vacio.

## Estados y validaciones

La pantalla debe contemplar estados explicitamente.

- Estado vacio cuando no existan Tipos.
- Estado sin indicadores para un Tipo valido.
- Estado de formulario en modo alta.
- Estado de formulario en modo edicion.
- Validaciones visibles y cercanas a cada campo.
- Acciones deshabilitadas cuando falte contexto necesario.

Las validaciones de Tipo e Indicador deben seguir usando reglas del componente Livewire, pero con mensajes y ubicacion mas claros dentro de la interfaz.

## Lineamientos visuales

- Reutilizar el lenguaje de tarjetas, filtros, encabezados y tablas ya presente en vistas administrativas recientes.
- Evitar bloques planos con borde simple como composicion principal.
- Destacar el Tipo activo como unidad fuerte de trabajo.
- Mostrar el selector con una etiqueta visual estable y legible.
- Mantener buena legibilidad en escritorio y resolucion util en pantallas medianas.
- No usar modales como patron principal del flujo.

## Ajustes tecnicos previstos

El componente Livewire de Indicadores necesitara una reorganizacion de estado para acompañar el layout.

Minimos cambios esperados:

- Separar con claridad el Tipo activo del formulario de Tipo.
- Separar con claridad el Indicador en edicion del formulario de Indicador.
- Garantizar que crear Tipo persista tambien el selector.
- Mantener la recarga de datos agrupada por Tipo.
- Corregir textos de feedback y confirmacion inconsistentes.

No se requiere redisenar modelos, migraciones ni relaciones Eloquent existentes.

## Manejo de errores

- Si una eliminacion falla por restricciones de datos, debe mostrarse un mensaje claro y especifico.
- Si el guardado falla por validacion, la pantalla conserva el contexto actual y resalta los campos invalidos.
- Si no hay datos disponibles, debe mostrarse un estado vacio explicativo, no una tabla rota o un bloque en blanco.

## Pruebas

Validaciones deseables para esta iteracion:

- Crear Tipo guardando nombre y selector.
- Editar Tipo manteniendo el mismo contexto activo.
- Crear Indicador asociado al Tipo activo.
- Editar Indicador desde la tabla.
- Eliminar Indicador con feedback correcto.
- Eliminar Tipo y recomponer seleccion activa.
- Render correcto del estado vacio sin Tipos.

Si el entorno local sigue bloqueado por base de datos, al menos deben validarse sintaxis, errores del editor y coherencia del flujo en la interfaz.

## Resultado esperado

La gestion de Indicadores debe pasar de ser una pantalla CRUD generica a una superficie administrativa clara, contextual y consistente con el resto del sistema. El usuario debe entender de inmediato que trabaja dentro de un Tipo, que ese Tipo define el selector, y que los Indicadores visibles pertenecen a ese contexto.
