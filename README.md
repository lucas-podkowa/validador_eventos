# Validador de Eventos y Certificados

Sistema web para gestionar el ciclo completo de eventos institucionales de la Facultad de Ingeniería: alta y administración de eventos, inscripción pública, importación masiva de participantes, control de asistencias, revisiones, emisión de certificados y validación pública mediante QR.

## Tecnología

- PHP 8.2+
- Laravel 11
- Livewire 3
- Laravel Jetstream 5 con Fortify y Sanctum
- Spatie Laravel Permission para roles y permisos
- Tailwind CSS 3 + Vite 5 + Axios
- DomPDF para certificados e informes en PDF
- PhpSpreadsheet y FastExcel para importación/exportación de planillas
- Base de datos relacional compatible con Laravel; en despliegues reales se utiliza una base persistente y el proyecto viene configurado por defecto para cache y colas en base de datos

## Problema

La gestión de eventos académicos y de extensión involucra varias tareas que suelen quedar dispersas entre formularios, planillas manuales, correos y archivos sueltos: publicar eventos, registrar participantes, asignar responsables, controlar asistencia, revisar aprobaciones, emitir certificados y validar su autenticidad.

Cuando ese circuito no está centralizado aparecen problemas de trazabilidad, duplicación de datos, errores operativos y dificultades para mantener criterios uniformes entre distintas áreas y tipos de evento.

## Solución

Este proyecto centraliza la operación completa de los eventos en una sola plataforma. El sistema permite administrar eventos y sus responsables, habilitar planillas de inscripción, registrar participantes en forma pública o masiva, controlar asistencias por sesión, procesar aprobaciones, generar certificados con plantillas por categoría y validar participantes o certificados desde enlaces y códigos QR.

El resultado es un flujo de trabajo consistente, auditable y reutilizable para eventos con necesidades administrativas, académicas y de certificación.

## Funcionalidades principales

- Gestión de eventos con estados, responsables, gestores y datos generales.
- Clasificación por categoría de evento y por tipo de evento.
- Administración de categorías con plantillas de certificados asociadas.
- CRUD de tipos de evento.
- Habilitación y edición de planillas de inscripción.
- Inscripción pública de participantes desde enlaces externos.
- Importación masiva de participantes desde archivos CSV y XLSX.
- Inscripción de staff y colaboradores.
- Gestión de participantes y consulta centralizada.
- Procesamiento de aprobaciones por parte de revisores.
- Registro de asistencias por sesiones del evento.
- Emisión de certificados en PDF con plantilla predefinida o imagen manual.
- Validación pública de participantes y certificados mediante QR.
- Generación de informes y documentos PDF.
- Envío de correos de confirmación, credenciales y certificados.

## Roles del sistema

- Administrador: administra eventos, usuarios, categorías, tipos de evento, indicadores, informes y certificados.
- Gestor: opera eventos, participantes y planillas.
- Revisor: procesa aprobaciones.
- Colaborador: registra asistencias.
- Invitado: puede autenticarse pero no accede a panel operativo.

## Flujo general de uso

1. Un administrador crea el evento y define su categoría, tipo, fechas, responsable e indicadores.
2. Se habilita la planilla de inscripción y se publica el acceso al formulario o se importan participantes en lote.
3. Se asignan gestores y, cuando corresponde, staff o colaboradores.
4. Durante la ejecución del evento se administran sesiones y asistencias.
5. Los revisores procesan aprobaciones.
6. Finalmente se emiten certificados y se validan desde QR o enlaces públicos.

## Estructura del proyecto

```text
app/
├── Console/Commands/      # Comandos Artisan propios
├── Http/Controllers/      # Controladores HTTP públicos
├── Livewire/              # Componentes funcionales del sistema
│   └── Admin/             # Paneles administrativos
├── Mail/                  # Correos del sistema
├── Models/                # Entidades de dominio
└── Providers/             # Configuración de servicios

config/                    # Configuración de Laravel
database/
├── migrations/            # Esquema de base de datos
├── factories/
└── seeders/               # Datos iniciales de referencia

public/                    # Punto de entrada web y assets públicos
resources/
├── css/                   # Tailwind CSS
├── js/                    # Bootstrap JS de la app
└── views/                 # Vistas Blade y vistas de Livewire

routes/                    # Rutas web, api y consola
storage/                   # Logs, cachés y archivos generados
tests/                     # Pruebas automatizadas
```

## Módulos destacados

- Eventos: alta, edición, seguimiento por estado y asignación de gestores.
- Planillas: apertura, cierre, edición e importación de participantes.
- Participantes: consulta, inscripción y gestión de relaciones con eventos.
- Asistencias: sesiones por evento y registro de presencia.
- Certificados: emisión, almacenamiento y validación.
- Administración: usuarios, categorías, tipos de evento e indicadores.
- Informes: generación de documentos y reportes en PDF.

## Requisitos para desarrollo local

- PHP 8.2 o superior con extensiones habituales para Laravel.
- Composer.
- Node.js 18 o superior y npm.
- Base de datos relacional compatible con Laravel.

## Puesta en marcha local

```bash
git clone <url-del-repositorio>
cd validador
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
php artisan serve
```

Notas:

- El seeder carga datos de referencia como tipos de evento, indicadores, roles, permisos y usuarios de ejemplo.
- La aplicación utiliza archivos públicos en storage/app/public, por lo que el enlace simbólico de storage es obligatorio.
- Los certificados se almacenan en discos de storage y su acceso depende de la configuración del entorno.

## Comandos útiles

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
npm run dev
npm run build
```

## Consideraciones de operación

- No deben versionarse los archivos generados dentro de storage/framework.
- Para producción conviene ejecutar solo migraciones incrementales y evitar comandos destructivos como migrate:fresh.
- La aplicación tiene una guía local de despliegue pensada para el servidor de producción, mantenida fuera del repositorio.
