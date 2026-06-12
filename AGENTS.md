# AGENTS.md — Validador de Eventos y Certificados

Laravel 11 + Livewire 3 + Jetstream/Sanctum app (Spanish UI) for managing institutional events: registration, attendance, approvals, certificate issuance, and QR validation. PHP 8.2+, Node 18+.

## First-time setup

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev        # or: npm run build
php artisan serve
```

`.env.example` defaults to `sqlite` for `DB_CONNECTION`; for a non-sqlite DB, fill the commented `DB_*` lines. `storage:link` is mandatory — the app serves generated files (certificates, templates) from `storage/app/public` via the `public/storage` symlink.

## Common commands

- Lint/format PHP: `vendor/bin/pint`
- Run tests: `php artisan test` (or `vendor/bin/phpunit`)
- Clear caches: `php artisan optimize:clear`
- Build frontend assets: `npm run build` (use `npm run dev` for HMR via Vite)

## Production safety rules (from `deploymend.md`, which is gitignored)

- Never run on a live DB: `migrate:fresh`, `db:wipe`, `db:seed`, or the custom `app:migrate-fresh-keep-participante` command. Use only `php artisan migrate --force` for schema updates.
- The seeder is **not idempotent** — it inserts tipos, indicadores, roles, permissions, and example users. Run only once on an empty DB, then replace/deactivate the demo accounts (`admin@mail.com`, `revisor@mail.com`, `colaborador@mail.com`, `invitado@mail.com`, all `password123`).
- `routes/web.php` defines a closure route (`/ver-certificado/{eventoParticipante}`), so **do not run `route:cache` or `optimize`** — they will break it. Use `config:cache` and `view:cache` only.
- `FILESYSTEM_DISK=local` for production. Certificates are served from the `private` disk (`storage/app/private/...`); they are not publicly listed.
- Recommended env for prod: `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `APP_DEBUG=false`.

## Architecture notes

- **Routing**: `routes/web.php`. Public routes (welcome, public event registration, QR validation) are unauthenticated; everything else is behind `auth:sanctum` + Jetstream's auth session + `verified`, and gated by Spatie permission middleware (`can:crear_eventos`, `can:eventos`, `can:ver_participantes`, `can:procesar_aprobaciones`, `can:asistencias`).
- **Livewire components** under `app/Livewire/`. Admin subnamespace `app/Livewire/Admin/` (Categorias, TiposEvento, Usuarios). All major features (events, planillas, participants, approvals, attendance, certificates, reports, indicators) are Livewire components, not controllers.
- **Models** in `app/Models/` (Evento, Participante, PlanillaInscripcion, SesionEvento, EventoParticipante, PlantillaCertificado, CategoriaEvento, etc.). The `participante` table is treated as the one "user data" table to preserve across destructive migrations — see the custom command above.
- **Permissions**: created in `DatabaseSeeder` via Spatie. New permission-gated routes must use one of the existing `can:` keys, or extend the seeder.
- **Frontend**: Tailwind 3 + Vite 5, entrypoints `resources/css/app.css` and `resources/js/app.js`. `tailwind.config.js` scans `resources/**` and `app/**/*.php` for class usage.

## Testing

- `phpunit.xml` has the `DB_CONNECTION=sqlite` / `:memory:` lines **commented out**, so the test suite uses whatever DB the current `.env` configures. Set `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` (or a dedicated test DB) before running tests, otherwise tests will hit your dev DB.
- Test env sets `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`.
- Most existing tests under `tests/Feature/` are stock Jetstream scaffolding; domain feature tests live alongside them.

## Conventions / gotchas

- UI copy, comments, and many seeders/migration names are in **Spanish** — match the existing terminology (e.g. `planilla_inscripcion`, `sesion_evento`, `participante`).
- Indent style: 4 spaces, LF line endings (see `.editorconfig`).
- There is no CI config in `.github/`. There is no `opencode.json` / `AGENTS.md` / `CLAUDE.md` in the repo other than this file.
- `deploymend.md` (intentionally misspelled filename) holds the production runbook and is **gitignored** — it's only on the server, don't expect it locally.
- `package.json` declares `@tailwindcss/vite` as a dependency, but `vite.config.js` and `tailwind.config.js` use the classic Tailwind 3 + PostCSS setup via `laravel-vite-plugin`. Don't add Tailwind v4 directives unless you migrate the build.
- Generated/runtime paths to never commit: anything under `storage/framework`, `public/build`, `public/storage`, `public/hot`, `.phpunit.result.cache`.
