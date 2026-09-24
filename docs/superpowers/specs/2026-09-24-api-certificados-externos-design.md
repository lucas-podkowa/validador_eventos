# Diseño: API de certificados externos (Tutores Académicos PPS)

Fecha: 2026-09-24

## Resumen

El sistema PPS (Prácticas Profesionales Supervisadas) emitirá certificados a Tutores Académicos
desde esta aplicación, centralizando aquí la generación. PPS envía los datos vía API autenticada y
el validador resuelve una plantilla ya configurada, genera el PDF (dompdf, reutilizando
`resources/views/certificado.blade.php`), guarda el archivo en el disco `private`, devuelve un ID y
una URL pública de verificación (la misma del QR) y notifica por email al tutor.

El tutor debe poder ver y descargar su certificado al iniciar sesión. Para eso el certificado se
asocia a un registro `participante`, que es el que enlaza con la cuenta `User`
(`participante.user_id`). Si el tutor todavía no tiene cuenta, al registrarse con DNI+email
coincidentes el vínculo se crea mediante `VincularParticipante` (ya existente).

## Alcance (MVP)

- `POST /api/v1/certificados` (emitir).
- `GET /api/v1/certificados/{id}` (consultar estado/metadatos).
- Validación pública `GET /verificar/{codigo}`.
- Email al tutor (encolado).
- Gestión de credenciales por comandos Artisan + UI Livewire.

Fuera del MVP: anulación y descarga del PDF por API. La columna `estado` se crea igual para no
re-migrar después.

## Seguridad

| Capa | Mecanismo |
|---|---|
| Transporte | HTTPS en producción |
| Autenticación | Bearer token Sanctum (hash SHA-256, revocable, expirable) |
| Autorización | Abilities `certificados:emitir`, `certificados:leer` vía `ability:` |
| Cliente habilitado | `api_cliente.activo`; middleware devuelve 403 si inactivo |
| Abuso | RateLimiter por `api_cliente_id` (60/min por defecto) → 429 |
| Integridad de entrada | `FormRequest` con whitelist; PPS nunca envía layout/HTML |
| Auditoría | Emisión registra `api_cliente_id`, token, IP y `user_agent` |
| Duplicados | `external_ref` única por cliente (idempotencia) |

El tokenable es un modelo propio `ApiCliente` (con `HasApiTokens`), no un `User`, para auditar qué
software emitió cada certificado.

## Modelo de datos

### `api_cliente`
`id`, `nombre`, `descripcion`, `activo` (bool), `scopes` (json nullable), timestamps.
Usa `HasApiTokens`.

### `certificado_externo`
`id` (uuid), `api_cliente_id` (FK), `external_ref`, `tipo`, `plantilla_certificado_id` (FK nullable),
`contexto_id` (FK nullable), `participante_id` (FK nullable), `datos` (json), `receptor_dni`,
`receptor_email`, `match_estado` (`auto`|`revisar`), `match_detalle` (json), `certificado_path`,
`qrcode` (longText SVG), `codigo_verificacion` (unique), `estado` (`emitido`|`anulado`, default
`emitido`), timestamps.

- Unique `(api_cliente_id, external_ref)` → idempotencia.
- Unique `codigo_verificacion`.

### Plantillas
`PlantillaCertificado::TIPOS` incorpora `tutor_academico`. La plantilla se marca con ese `tipo` y,
opcionalmente, `por_defecto`, usando el editor dinámico existente (`Admin/ContextoPlantillas`).

## Resolución de identidad — `ResolverParticipanteExterno`

Normaliza el payload (`dni`, `mail_norm`, `telefono_norm`, `apellido_norm`, `nombre_norm`) con
`NormalizadorIdentidad` y aplica la siguiente cascada:

| # | Clave de match | Confianza | `match_estado` | Auto `user_id` |
|---|---|---|---|---|
| 1 | DNI + mail | Alta | `auto` | Sí (si `User` coincide en DNI+mail) |
| 2 | DNI + teléfono | Media | `revisar` | No |
| 3 | DNI + apellido + teléfono | Media-alta | `auto` | Sí (si `User` coincide en DNI+mail) |
| 4 | Solo DNI (la fila existe) | Baja | `revisar` | No |
| 5 | Sin DNI, mail exacto (unique) | Media | `revisar` | No |
| 6 | Sin DNI, teléfono + apellido (`BuscarParticipanteSimilar`) | Baja-media | `revisar` | No |
| 7 | Sin match | — | `auto` | Crear participante nuevo |

Reglas:

- Nunca se sobrescriben datos del participante existente; solo se completan `mail`/`telefono` si
  estaban vacíos.
- Por los UNIQUE de `participante.dni` y `participante.mail`, los tiers 4-6 asocian y marcan
  `revisar` en lugar de crear una fila duplicada.
- La emisión nunca se bloquea por un match dudoso: PDF, QR y email se generan igual. Solo queda
  pendiente la visibilidad en Mis Certificados hasta que un administrador resuelva el vínculo.
- `user_id` se asigna solo si el `User` coincide con el participante en **DNI + mail**.

El servicio devuelve `(Participante, match_estado, match_detalle)`.

## Contrato API

### Request

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
    "email": "juan@fio.unam.edu.ar",
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
  },
  "fecha_emision": null
}
```

`tutor.telefono` es obligatorio (`participante.telefono` es NOT NULL).

### Respuesta 201

```json
{
  "data": {
    "id": "uuid",
    "external_ref": "PPS-TUT-2026-000123",
    "estado": "emitido",
    "match_estado": "auto",
    "receptor": { "nombre": "Perez, Juan Carlos", "dni": "30111222" },
    "verificacion_url": "https://…/verificar/AbC123xyz",
    "emitido_en": "2026-09-24T12:00:00-03:00"
  }
}
```

### Errores

- `401` sin token.
- `403` sin ability o cliente inactivo.
- `422` validación.
- `429` rate limit (+ `Retry-After`).
- Replay idempotente: `200` con la emisión existente y header `Idempotent-Replay: true`.

## Flujo de emisión

`GenerarCertificadoExterno`:

1. Resuelve la plantilla por `plantilla_codigo` o `(contexto_id, tipo='tutor_academico', por_defecto)`.
2. Construye variables con `CertificadoVariables::paraExterno()` (mismas claves que `paraEvento`).
3. Genera el QR (BaconQrCode) apuntando a `/verificar/{codigo_verificacion}`.
4. Renderiza con `Pdf::loadView('certificado', …)` sin modificar la vista existente.
5. Guarda en `private` en `certificados_externos/{año}/{tipo}/{apellido}_{nombres}_{dni}.pdf`.
6. En transacción crea la fila; ante violación de unique devuelve la existente.
7. Encola `CertificadoTutorMail` (adjunto desde `private`).

Generación síncrona (dompdf) para poder devolver la URL; el email va a cola.

## Validación pública

`GET /verificar/{codigo}` → `VerificacionExternaController`, sin auth. Muestra tutor, tipo, período y
estado; si no existe o está anulado usa `cert_no_valido.png`.

## Visibilidad del tutor

- Sección separada "Tutorías / Prácticas profesionales" en `MisCertificados` (certificados externos
  del `participante` con `estado=emitido` y archivo existente en `private`).
- `CertificadoDescargaController::externo()` y rutas `mis_certificados.externo`, autorización
  dueño o Administrador/Gestor.

## Administración y reconciliación

- Permiso `administrar_api` (seeder + comando de asignación para producción, porque el seeder no es
  idempotente).
- Livewire `Admin/ApiClientes`: CRUD de clientes, generar token con abilities (mostrado una vez),
  listar/revocar tokens, conteo de emisiones.
- Livewire `Admin/CertificadosExternos`: listado, filtro `match_estado=revisar`, descarga y acción
  "vincular a usuario" (override por DNI con confirmación).
- Comandos: `api:cliente:crear`, `api:token:crear`, `api:token:revocar` y reconciliación de
  certificados externos sin vincular.

## Testing

Feature tests: `401/403/422/429`; emisión `201` + archivo en `private` + fila; idempotencia; cada tier
de matching (incluye asociar+`revisar` sin crear); vinculación automática solo tier 1/3;
no-vinculación con email distinto; visibilidad en MisCertificados; descarga autorizada; validación
pública. SQLite en memoria según AGENTS.md.

## Notas

- No se requiere un rol nuevo para el tutor: al estar vinculado al participante accede a
  `mis_certificados` como cualquier participante.
- La falta de verificación de email en el registro es la razón por la que la vinculación automática
  se limita a DNI+mail.
