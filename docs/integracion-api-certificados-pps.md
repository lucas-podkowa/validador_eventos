# API de Certificados Externos — Guía de integración para PPS

Esta guía describe cómo el sistema **PPS** (Prácticas Profesionales Supervisadas) debe consumir
la API del **validador/gestor de eventos** para emitir certificados de **Tutores Académicos**.

El validador centraliza la emisión: PPS envía los datos del tutor y de la práctica, y el validador
genera el PDF, lo guarda, notifica por email al tutor y publica una URL pública de verificación (QR).
El tutor podrá ver y descargar su certificado al iniciar sesión en el validador.

---

## 1. Entornos y URLs base

| Entorno | Validador (API) | PPS |
|---|---|---|
| Local | `http://localhost:8000` → base API `http://localhost:8000/api/v1` | `http://localhost:3000` |
| Producción | `https://acreditar.fio.unam.edu.ar` → base API `https://acreditar.fio.unam.edu.ar/api/v1` | `https://pps.fio.unam.edu.ar` |

> La llamada es **servidor a servidor** (PPS backend → validador). No se necesita CORS ni exponer el
> token en el navegador. Usar siempre HTTPS en producción.

Todas las rutas llevan el prefijo `/api/v1`:

- Emitir: `POST {BASE}/certificados`
- Consultar: `GET {BASE}/certificados/{id}`

---

## 2. Autenticación

Se usa un **token Bearer** (Laravel Sanctum) asociado a un *cliente de API*. Cada instalación de PPS
debe tener su propio token, que **solo se muestra una vez** al generarlo y debe guardarse en variables
de entorno/secretos del servidor (nunca en el frontend ni en el repositorio).

### 2.1 Obtener el token (lo hace el administrador del validador)

Opción A — Panel web: `/admin/api-clientes` → crear/linkear el cliente PPS → **Generar token**,
tildando las habilidades necesarias (`certificados:emitir` y opcionalmente `certificados:leer`).

Opción B — Comandos:

```bash
php artisan api:cliente:crear PPS
php artisan api:token:crear PPS --ability=certificados:emitir --ability=certificados:leer --name=pps-prod
```

El comando imprime el token una sola vez. Guardarlo en PPS como `VALIDADOR_API_TOKEN`.

### 2.2 Habilidades (abilities)

| Habilidad | Permite |
|---|---|
| `certificados:emitir` | `POST /api/v1/certificados` |
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

## 3. Emitir un certificado

`POST {BASE}/certificados`

### 3.1 Cuerpo de la petición

```json
{
  "external_ref": "PPS-TUT-2026-000123",
  "tipo": "tutor_academico",
  "plantilla_codigo": "tutor_academico_default",
  "contexto_id": null,
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

### 3.2 Campos

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `external_ref` | string (≤191) | Sí | Identificador único **de PPS**. Clave de idempotencia (ver §5). |
| `tipo` | string | Sí | Para tutores usar `"tutor_academico"`. |
| `plantilla_codigo` | string | No | Nombre de la plantilla configurada en el validador. Si se omite, se usa la marcada por defecto para el `tipo`. |
| `contexto_id` | integer | No | Contexto institucional (jornada/año). Reservado; normalmente no se envía. |
| `tutor.apellido` | string (≤100) | Sí | |
| `tutor.nombres` | string (≤100) | Sí | Se usa `nombres` (no `nombre`). |
| `tutor.dni` | string, 6–12 dígitos | Sí | Se usa para vincular con la cuenta del tutor. |
| `tutor.email` | email (≤191) | Sí | Se usa para vincular y para enviar el certificado. |
| `tutor.telefono` | string (≤30) | Sí | Requerido por el modelo de datos del validador. |
| `tutor.cargo` | string (≤100) | No | Ej. "Tutor Académico". |
| `practica.carrera` | string (≤191) | No | |
| `practica.estudiante_apellido_nombres` | string (≤191) | No | Formato "Apellido, Nombres". |
| `practica.estudiante_dni` | string, 6–12 dígitos | No | |
| `practica.institucion` | string (≤191) | No | |
| `practica.periodo_inicio` | fecha `YYYY-MM-DD` | No | |
| `practica.periodo_fin` | fecha `YYYY-MM-DD` | No | Debe ser ≥ `periodo_inicio`. |
| `practica.horas` | integer (0–100000) | No | |
| `practica.resolucion` | string (≤191) | No | |

Los campos desconocidos se ignoran. `fecha_emision` está reservado y no se usa por ahora.

### 3.3 Respuesta exitosa (`201 Created`)

```json
{
  "data": {
    "id": "5f2c1c9e-8a4b-4c2e-9d10-3b7a1e2f4a55",
    "external_ref": "PPS-TUT-2026-000123",
    "tipo": "tutor_academico",
    "estado": "emitido",
    "match_estado": "auto",
    "receptor": {
      "nombre": "Perez, Juan Carlos",
      "dni": "30111222",
      "email": "juan.perez@fio.unam.edu.ar"
    },
    "verificacion_url": "https://acreditar.fio.unam.edu.ar/verificar/AbC123...",
    "emitido_en": "2026-09-24T12:00:00-03:00"
  }
}
```

| Campo | Descripción |
|---|---|
| `id` | UUID del certificado. Usar para `GET /certificados/{id}`. |
| `estado` | `emitido` (o `anulado` a futuro). |
| `match_estado` | `auto` (vínculo confiable) o `revisar` (revisión manual; ver §6). |
| `verificacion_url` | URL pública del QR. PPS puede guardarla y compartirla. |
| `emitido_en` | ISO-8601. |

### 3.4 Ejemplos de llamada

**cURL**

```bash
curl -X POST "http://localhost:8000/api/v1/certificados" \
  -H "Authorization: Bearer $VALIDADOR_API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "external_ref": "PPS-TUT-2026-000123",
    "tipo": "tutor_academico",
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

export async function emitirCertificadoTutor(datos) {
  const res = await fetch(`${VALIDADOR_URL}/api/v1/certificados`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${TOKEN}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(datos),
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
    ->post(config('services.validador.url').'/api/v1/certificados', $datos);

if ($response->successful()) {
    $certificado = $response->json('data');
}
```

---

## 4. Consultar un certificado

`GET {BASE}/certificados/{id}` — requiere `certificados:leer`. Solo el cliente que lo emitió puede
verlo (otro cliente obtiene `404`).

```bash
curl "http://localhost:8000/api/v1/certificados/5f2c1c9e-..." \
  -H "Authorization: Bearer $VALIDADOR_API_TOKEN" -H "Accept: application/json"
```

Devuelve el mismo objeto `data` de la emisión.

---

## 5. Idempotencia y reintentos (importante)

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

## 6. Cómo decide el validador a qué tutor pertenece el certificado

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

---

## 7. Errores

| Código | Cuándo | Cuerpo típico |
|---|---|---|
| `401` | Falta el token o es inválido | `{ "message": "Unauthenticated." }` |
| `403` | Token sin la habilidad requerida, o cliente deshabilitado | `{ "message": "Invalid ability provided." }` / `{ "message": "El cliente de API está inactivo." }` |
| `404` | Certificado inexistente o de otro cliente (en `GET`) | `{ "message": "No query results..." }` |
| `422` | Datos inválidos o falta plantilla configurada | `{ "message": "...", "errors": { "tutor.dni": ["..."] } }` |
| `429` | Superó el límite de peticiones | `{ "message": "Too Many Requests." }` + header `Retry-After` |

Ejemplo de error de validación:

```json
{
  "message": "tutor.telefono field is required. (and 1 more error)",
  "errors": {
    "tutor.telefono": ["tutor.telefono field is required."],
    "tutor.email": ["tutor.email must be a valid email address."]
  }
}
```

---

## 8. Límites y buenas prácticas

- **Rate limit:** 60 peticiones por minuto por cliente. Para emisiones masivas, hacer batching con
  pausas o coordinar un límite mayor con el administrador del validador.
- **Un certificado por tutor y práctica.** Usar `external_ref` para no emitir dos veces lo mismo.
- **No enviar HTML ni plantillas.** La estética y los textos los define el validador.
- **El token es un secreto.** Guardarlo en el servidor de PPS; rotarlo periódicamente
  (`api:token:crear` / `api:token:revocar`).
- **Verificación pública:** la `verificacion_url` es pública y puede incluirse en la UI de PPS. No
  requiere autenticación.

---

## 9. Checklist de integración

- [ ] Crear el cliente API y generar el token (panel `/admin/api-clientes` o comando).
- [ ] Configurar en PPS: `VALIDADOR_URL` (`http://localhost:8000` local / `https://acreditar.fio.unam.edu.ar` prod) y `VALIDADOR_API_TOKEN`.
- [ ] Configurar una plantilla `tipo=tutor_academico` en el validador (marcada por defecto) o acordar su `plantilla_codigo`.
- [ ] Implementar `POST /api/v1/certificados` con `external_ref` persistida e idempotencia.
- [ ] Manejar `401/403/422/429/5xx` y reintentos con backoff.
- [ ] Probar en local (PPS `:3000` → validador `:8000`) y verificar el PDF, el email y la URL del QR.
- [ ] Al desplegar: HTTPS, token de producción y dominio `https://acreditar.fio.unam.edu.ar`.
