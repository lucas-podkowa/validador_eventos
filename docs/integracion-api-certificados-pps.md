# API de Certificados Externos — Guía de integración para PPS

Esta guía describe cómo el sistema **PPS** (Prácticas Profesionales Supervisadas) debe consumir
la API del **validador/gestor de eventos** para emitir certificados de **Tutores Académicos**.

El validador centraliza la emisión: PPS envía los datos del tutor y de la práctica, y el validador
genera el PDF, lo guarda, notifica por email al tutor y publica una URL pública de verificación (QR).
El tutor podrá ver y descargar su certificado al iniciar sesión en el validador.

> **Cambio importante (2026):** las plantillas de certificado ahora viven **dentro de un contexto**
> institucional. La emisión externa exige `contexto_id`. Antes de emitir, PPS debe descubrir el
> contexto de PPS y su plantilla con `GET /api/v1/contextos`. Las llamadas que no envíen
> `contexto_id` serán rechazadas con `422`.

---

## 1. Entornos y URLs base

| Entorno | Validador (API) | PPS |
|---|---|---|
| Local | `http://localhost:8000` → base API `http://localhost:8000/api/v1` | `http://localhost:3000` |
| Producción | `https://acreditar.fio.unam.edu.ar` → base API `https://acreditar.fio.unam.edu.ar/api/v1` | `https://pps.fio.unam.edu.ar` |

> La llamada es **servidor a servidor** (PPS backend → validador). No se necesita CORS ni exponer el
> token en el navegador. Usar siempre HTTPS en producción.

Todas las rutas llevan el prefijo `/api/v1`:

- Descubrir contextos/plantillas: `GET {BASE}/contextos`
- Emitir: `POST {BASE}/certificados`
- Consultar: `GET {BASE}/certificados/{id}`

---

## 2. Autenticación

Se usa un **token Bearer** (Laravel Sanctum) asociado a un *cliente de API*. Cada instalación de PPS
debe tener su propio token, que **solo se muestra una vez** al generarlo y debe guardarse en variables
de entorno/secretos del servidor (nunca en el frontend ni en el repositorio).

### 2.1 Obtener el token (lo hace el administrador del validador)

Opción A — Panel web: `/admin/api-clientes` → crear/linkear el cliente PPS → **Generar token**,
tildando las habilidades necesarias (`certificados:emitir`, `certificados:contextos` y opcionalmente
`certificados:leer`).

Opción B — Comandos:

```bash
php artisan api:cliente:crear PPS
php artisan api:token:crear PPS \
  --ability=certificados:emitir \
  --ability=certificados:contextos \
  --ability=certificados:leer \
  --name=pps-prod
```

El comando imprime el token una sola vez. Guardarlo en PPS como `VALIDADOR_API_TOKEN`.

### 2.2 Habilidades (abilities)

| Habilidad | Permite |
|---|---|
| `certificados:emitir` | `POST /api/v1/certificados` |
| `certificados:contextos` | `GET /api/v1/contextos` (descubrir contexto y plantilla) |
| `certificados:leer` | `GET /api/v1/certificados/{id}` |

Si el cliente está deshabilitado, todas las llamadas devuelven `403`.

### 2.3 Uso del token

```http
Authorization: Bearer <VALIDADOR_API_TOKEN>
Accept: application/json
Content-Type: application/json
```

> Enviá siempre `Accept: application/json` para recibir errores en JSON (si falta, Laravel puede
> responder con una redirección HTML).

---

## 3. Descubrir el contexto y la plantilla de PPS

`GET {BASE}/contextos` — requiere `certificados:contextos`.

Devuelve los contextos que tienen al menos una plantilla emitible (con layout). Se puede filtrar por
tipo de reconocimiento con `?tipo=tutor` (también se acepta el alias `tutor_academico`).

```bash
curl "http://localhost:8000/api/v1/contextos?tipo=tutor" \
  -H "Authorization: Bearer $VALIDADOR_API_TOKEN" \
  -H "Accept: application/json"
```

Respuesta:

```json
{
  "data": [
    {
      "id": 45,
      "nombre": "Prácticas Profesionales Supervisadas",
      "tipo": "programa",
      "denominacion": "Prácticas Profesionales Supervisadas",
      "institucion": "Facultad de Ingeniería UNaM",
      "anio": null,
      "resolucion": "Res. CD 123/2026",
      "lugar": null,
      "plantillas": [
        {
          "id": 12,
          "codigo": "tutor_academico_default",
          "tipo": "tutor",
          "por_defecto": true
        }
      ]
    }
  ]
}
```

| Campo | Descripción |
|---|---|
| `id` | `contexto_id` que PPS debe enviar al emitir. |
| `nombre` | Nombre del contexto (ej. la edición/programa). |
| `tipo` | `edicion`, `programa` o `subprograma`. |
| `plantillas[].codigo` | Valor para `plantilla_codigo` (opcional al emitir). |
| `plantillas[].por_defecto` | Si es `true`, se usa cuando PPS no envía `plantilla_codigo`. |

**Recomendación:** obtener el contexto una vez y persistirlo en PPS como `VALIDADOR_CONTEXTO_ID`
(junto con `VALIDADOR_PLANTILLA_CODIGO` si hay más de una variante). No hace falta descubrirlo en
cada emisión.

---

## 4. Emitir un certificado

`POST {BASE}/certificados`

### 4.1 Cuerpo de la petición

```json
{
  "external_ref": "PPS-TUT-2026-000123",
  "tipo": "tutor_academico",
  "contexto_id": 45,
  "plantilla_codigo": "tutor_academico_default",
  "tutor": {
    "apellido": "Perez",
    "nombres": "Juan Carlos",
    "dni": "30111222",
    "email": "juan.perez@fio.unam.edu.ar",
    "telefono": "3764123456",
    "cargo": "Tutor Académico"
  },
  "practica": {
    "carrera": "Ingeniería Civil",
    "estudiante_apellido_nombres": "Gomez, Ana",
    "estudiante_dni": "40123456",
    "institucion": "FIO - UNaM",
    "periodo_inicio": "2026-03-01",
    "periodo_fin": "2026-06-30",
    "horas": 120,
    "resolucion": "Res. 123/2026"
  }
}
```

### 4.2 Campos

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `external_ref` | string (≤191) | Sí | Identificador único **de PPS**. Clave de idempotencia (ver §6). |
| `tipo` | string | Sí | Para tutores usar `"tutor_academico"` (alias de `"tutor"`). |
| `contexto_id` | integer | **Sí** | Contexto de PPS obtenido en §3. Define dónde y con qué firmantes se emite. |
| `plantilla_codigo` | string | No | Nombre de la plantilla. Si se omite, se usa la **predeterminada** del `(contexto, tipo)`. |
| `tutor.apellido` | string (≤100) | Sí | |
| `tutor.nombres` | string (≤100) | Sí | Se usa `nombres` (no `nombre`). |
| `tutor.dni` | string, 6–12 dígitos | Sí | Se usa para vincular con la cuenta del tutor. |
| `tutor.email` | email (≤191) | Sí | Se usa para vincular y para enviar el certificado. |
| `tutor.telefono` | string (≤30) | Sí | Requerido por el modelo de datos del validador. |
| `tutor.cargo` | string (≤100) | No | Ej. "Tutor Académico". Disponible como `{cargo}`. |
| `practica.carrera` | string (≤191) | No | Disponible como `{carrera}`. |
| `practica.estudiante_apellido_nombres` | string (≤191) | No | Formato "Apellido, Nombres". Token `{estudiante}`. |
| `practica.estudiante_dni` | string, 6–12 dígitos | No | Token `{estudiante_dni}`. |
| `practica.institucion` | string (≤191) | No | Si se omite, usa la institución del contexto. |
| `practica.periodo_inicio` | fecha `YYYY-MM-DD` | No | Define `{fecha_rango}` del certificado. |
| `practica.periodo_fin` | fecha `YYYY-MM-DD` | No | Debe ser ≥ `periodo_inicio`. |
| `practica.horas` | integer (0–100000) | No | Token `{horas}`. |
| `practica.resolucion` | string (≤191) | No | Si se omite, usa la resolución del contexto. |
| `fecha_emision` | fecha | No | Reservado; no se usa por ahora. |

Los campos desconocidos se ignoran.

### 4.3 Plantillas: predeterminada y múltiples variantes

- Cada plantilla pertenece a un `contexto` y a un `tipo_reconocimiento`.
- Si un `(contexto, tipo)` tiene **una sola** plantilla, esa queda marcada como predeterminada.
- Si tiene **varias**, el administrador debe marcar al menos una como *predeterminada*.
- Al emitir:
  - **Sin** `plantilla_codigo` → se usa la predeterminada del `(contexto, tipo)`.
  - **Con** `plantilla_codigo` → se usa esa variante (el nombre es único por `(contexto, tipo)`).
  - Si no hay predeterminada ni se envía código → `422`.

### 4.4 Respuesta exitosa (`201 Created`)

```json
{
  "data": {
    "id": "5f2c1c9e-8a4b-4c2e-9d10-3b7a1e2f4a55",
    "external_ref": "PPS-TUT-2026-000123",
    "tipo": "tutor",
    "alcance": "programa",
    "estado": "emitido",
    "match_estado": "auto",
    "receptor": {
      "nombre": "Perez, Juan Carlos",
      "dni": "30111222",
      "email": "juan.perez@fio.unam.edu.ar"
    },
    "contexto": {
      "id": 45,
      "nombre": "Prácticas Profesionales Supervisadas"
    },
    "plantilla": {
      "id": 12,
      "codigo": "tutor_academico_default"
    },
    "verificacion_url": "https://acreditar.fio.unam.edu.ar/verificar/AbC123...",
    "emitido_en": "2026-09-24T12:00:00-03:00"
  }
}
```

| Campo | Descripción |
|---|---|
| `id` | UUID del certificado. Usar para `GET /certificados/{id}`. |
| `tipo` | Slug del tipo de reconocimiento (`tutor`). |
| `alcance` | `programa` (tutores), `contexto` o `evento`. |
| `estado` | `emitido` (o `anulado` a futuro). |
| `match_estado` | `auto` (vínculo confiable) o `revisar` (revisión manual; ver §6). |
| `contexto` / `plantilla` | Contexto y plantilla efectivamente usados (trazabilidad). |
| `verificacion_url` | URL pública del QR. PPS puede guardarla y compartirla. |
| `emitido_en` | ISO-8601. |

### 4.5 Ejemplos de llamada

**cURL**

```bash
curl -X POST "http://localhost:8000/api/v1/certificados" \
  -H "Authorization: Bearer $VALIDADOR_API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "external_ref": "PPS-TUT-2026-000123",
    "tipo": "tutor_academico",
    "contexto_id": 45,
    "tutor": {
      "apellido": "Perez", "nombres": "Juan Carlos", "dni": "30111222",
      "email": "juan.perez@fio.unam.edu.ar", "telefono": "3764123456",
      "cargo": "Tutor Académico"
    },
    "practica": {
      "carrera": "Ingeniería Civil", "periodo_inicio": "2026-03-01",
      "periodo_fin": "2026-06-30", "horas": 120
    }
  }'
```

**Node.js (PPS en el puerto 3000)**

```js
const VALIDADOR_URL = process.env.VALIDADOR_URL ?? 'http://localhost:8000';
const TOKEN = process.env.VALIDADOR_API_TOKEN;
const CONTEXTO_ID = Number(process.env.VALIDADOR_CONTEXTO_ID);

export async function emitirCertificadoTutor(datos) {
  const res = await fetch(`${VALIDADOR_URL}/api/v1/certificados`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${TOKEN}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ contexto_id: CONTEXTO_ID, ...datos }),
  });

  if (res.status === 201 || res.status === 200) {
    return res.json(); // { data: { id, verificacion_url, ... } }
  }

  const error = await res.json().catch(() => ({}));
  throw new Error(`Emisión falló (${res.status}): ${error.message ?? 'error desconocido'}`);
}
```

**PHP (Laravel Http)**

```php
$response = Http::withToken(config('services.validador.token'))
    ->acceptJson()
    ->post(config('services.validador.url').'/api/v1/certificados', array_merge(
        ['contexto_id' => config('services.validador.contexto_id')],
        $datos,
    ));

if ($response->successful()) {
    $certificado = $response->json('data');
}
```

---

## 5. Consultar un certificado

`GET {BASE}/certificados/{id}` — requiere `certificados:leer`. Solo el cliente que lo emitió puede
verlo (otro cliente obtiene `404`).

```bash
curl "http://localhost:8000/api/v1/certificados/5f2c1c9e-..." \
  -H "Authorization: Bearer $VALIDADOR_API_TOKEN" -H "Accept: application/json"
```

Devuelve el mismo objeto `data` de la emisión.

---

## 6. Idempotencia y reintentos (importante)

`(cliente, external_ref)` es único. Si PPS reintenta la misma `external_ref` (por timeout o error de
red), el validador **no genera un duplicado**: devuelve la emisión existente con `200 OK` y el header:

```http
Idempotent-Replay: true
```

Recomendaciones para PPS:

- Generar `external_ref` estable y único por tutor/práctica (ej. `PPS-TUT-<año>-<id>`) y persistirlo
  antes de llamar a la API.
- Usar la misma `external_ref` en los reintentos.
- Reintentar ante `429` (respetando `Retry-After`) y ante `5xx`, con backoff exponencial.
- No reintentar `422` (corregir los datos) ni `401/403` (revisar credenciales/abilities).

---

## 7. Cómo decide el validador a qué tutor pertenece el certificado

El validador intenta vincular al tutor con un registro existente aplicando una cascada de claves y
marca el resultado en `match_estado`:

| Clave coincidente | `match_estado` | Vincula cuenta automáticamente |
|---|---|---|
| DNI + email | `auto` | Sí, si la cuenta coincide en DNI + email |
| DNI + apellido + teléfono | `auto` | Sí, si la cuenta coincide en DNI + email |
| DNI + teléfono | `revisar` | No |
| Solo DNI | `revisar` | No |
| Email exacto (DNI distinto) | `revisar` | No |
| Teléfono + apellido (sin DNI) | `revisar` | No |
| Sin coincidencias | `auto` | Se crea el registro del tutor |

**Recomendación para PPS:** enviar siempre `dni`, `email` y `telefono` reales y actualizados del
tutor. Así el match queda en `auto` y, si el tutor ya tiene cuenta en el validador, verá el
certificado al iniciar sesión. Si se registra más adelante con ese DNI + email, también lo verá.

Los casos `revisar` **no bloquean la emisión**: el PDF, el QR y el email se generan igual; solo
queda pendiente de revisión la visibilidad del certificado en el portal del tutor hasta que un
administrador resuelva el vínculo.

> Un mismo tutor puede tener **varios certificados** (una práctica = una `external_ref`). Todos quedan
> accesibles y el administrador puede verlos juntos en `/admin/certificados`.

---

## 8. Plantillas y tokens disponibles

La estética y los textos los define el validador. Las plantillas del contexto pueden usar tokens que
se completan con los datos del tutor y de la práctica:

| Token | Origen |
|---|---|
| `{apellido}`, `{nombres}`, `{apellido_nombres}`, `{dni}` | Tutor |
| `{contexto}`, `{contexto_nombre}`, `{contexto_denominacion}`, `{institucion}`, `{resolucion}`, `{lugar}` | Contexto |
| `{fecha_rango}` | Período de la práctica (o del contexto si no se envía) |
| `{cargo}`, `{carrera}`, `{estudiante}`, `{estudiante_dni}`, `{horas}` | Datos de la práctica |
| `{firmante_1_nombre}`, `{firmante_1_cargo}`, … | Firmantes del contexto |

PPS **no** envía HTML ni plantillas: solo datos.

---

## 9. Errores

| Código | Cuándo | Cuerpo típico |
|---|---|---|
| `401` | Falta el token o es inválido | `{ "message": "Unauthenticated." }` |
| `403` | Token sin la habilidad requerida, o cliente deshabilitado | `{ "message": "Invalid ability provided." }` / `{ "message": "El cliente de API está inactivo." }` |
| `404` | Certificado inexistente o de otro cliente (en `GET`) | `{ "message": "No query results..." }` |
| `422` | Datos inválidos, falta `contexto_id`, o no hay plantilla válida | `{ "message": "...", "errors": { ... } }` |
| `429` | Superó el límite de peticiones | `{ "message": "Too Many Requests." }` + header `Retry-After` |

Ejemplo de error de validación:

```json
{
  "message": "The contexto id field is required. (and 1 more error)",
  "errors": {
    "contexto_id": ["The contexto id field is required."],
    "tutor.email": ["The tutor.email field must be a valid email address."]
  }
}
```

Ejemplo de error de plantilla (contexto/tipo sin plantilla disponible):

```json
{
  "message": "No hay una plantilla por defecto para el tipo y contexto indicados. Configurá una plantilla predeterminada o enviá plantilla_codigo."
}
```

---

## 10. Límites y buenas prácticas

- **Rate limit:** 60 peticiones por minuto por cliente. Para emisiones masivas, hacer batching con
  pausas o coordinar un límite mayor con el administrador del validador.
- **Un certificado por tutor y práctica.** Usar `external_ref` para no emitir dos veces lo mismo.
- **Guardar `contexto_id`** una vez (descubierto en §3); no cambiarlo salvo que el validador lo
  indique.
- **No enviar HTML ni plantillas.** La estética y los textos los define el validador.
- **El token es un secreto.** Guardarlo en el servidor de PPS; rotarlo periódicamente
  (`api:token:crear` / `api:token:revocar`).
- **Verificación pública:** la `verificacion_url` es pública y puede incluirse en la UI de PPS. No
  requiere autenticación.

---

## 11. Checklist de integración

- [ ] Crear el cliente API y generar el token con `certificados:emitir` y `certificados:contextos`
      (panel `/admin/api-clientes` o comando).
- [ ] Configurar en PPS: `VALIDADOR_URL`, `VALIDADOR_API_TOKEN`, `VALIDADOR_CONTEXTO_ID` y, si aplica,
      `VALIDADOR_PLANTILLA_CODIGO`.
- [ ] Descubrir el contexto con `GET /api/v1/contextos?tipo=tutor` y guardar su `id`.
- [ ] Configurar en el validador una plantilla `tipo=tutor` en ese contexto y marcarla como
      **predeterminada** (o acordar el `plantilla_codigo`).
- [ ] Implementar `POST /api/v1/certificados` con `contexto_id`, `external_ref` persistida e idempotencia.
- [ ] Manejar `401/403/422/429/5xx` y reintentos con backoff.
- [ ] Probar en local (PPS `:3000` → validador `:8000`) y verificar el PDF, el email y la URL del QR.
- [ ] Al desplegar: HTTPS, token de producción y dominio `https://acreditar.fio.unam.edu.ar`.
