Project-specific Copilot instructions for ImpartCMS (Laravel 12 overlay)

Overview
- This repository is a patch pack applied on top of a fresh Laravel 12 + Breeze (Blade) app. Key behaviour is implemented in `App\Support` and registered via `App\Providers\CmsServiceProvider`.
- Primary responsibilities: page rendering with shortcodes, admin UI (under configurable admin path), module discovery/registration, and lightweight form handling.

Quick setup (commands)
- Use the included composer script: `composer run setup` (runs install, copies .env, key generation, migrations, npm install/build).
- Manual steps (see README): add `App\Providers\CmsServiceProvider::class` to [bootstrap/providers.php](bootstrap/providers.php) and apply the Gate snippet in [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) (see `patch_notes/APP_SERVICE_PROVIDER_GUARD_SNIPPET.txt`).
- Common commands:
  - `composer install`
  - `php artisan key:generate`
  - `php artisan migrate`
  - `php artisan db:seed`
  - `npm install`
  - `npm run build`

Architecture & important files
- Service registration: `App\Providers\CmsServiceProvider` registers `ModuleManager` and `Cms` singletons and registers enabled module providers at boot.
  - See: [app/Providers/CmsServiceProvider.php](app/Providers/CmsServiceProvider.php)
- Module discovery: `ModuleManager` finds `modules/{name}/module.json` and also falls back to a `modules` DB table when present.
  - Module JSON example and provider format are in code comments in [app/Support/ModuleManager.php](app/Support/ModuleManager.php)
- Core rendering and shortcodes: `App\Support\Cms` handles `@cmsContent()` blade directive and shortcodes like `[form ...]` and `[icon ...]`.
  - See: [app/Support/Cms.php](app/Support/Cms.php)
- Configuration: [config/cms.php](config/cms.php)
  - `admin_path` (default `admin`) controls admin URL prefix
  - `modules_path` controls where modules are discovered
  - `allow_raw_html` toggles whether non-shortcode content is output raw (default true)

Routing & conventions
- Route ordering is important: preview routes and admin routes must appear before the public catch-all page route. See [routes/web.php](routes/web.php).
- Route model bindings explicitly include trashed models for preview/trash endpoints (`pagePreview`, `pageTrash`, `formTrash`, etc.). When editing routes or controllers, preserve those bindings.
- Admin routes are protected by the `can:access-admin` gate (AppServiceProvider changes required). The `users.is_admin` boolean is used for admin access.

Security and data flow notes
- Shortcode handling is intentional and opinionated:
  - `[form slug="..."]` loads an active `Form` record and renders `cms.forms.embed` view.
  - `[icon ...]` supports inline SVG (sanitised) or safe FontAwesome/Lucide usage — the code enforces allowed characters and hex colour safety.
  - `config('cms.allow_raw_html')` controls HTML escaping for non-shortcode content; be careful when changing it.
- Module provider registration is defensive: `ModuleManager::registerEnabledProviders()` will only register providers if classes exist and works when DB tables are not migrated yet.

Database, tests & CI hints
- The repo ships `impartcms_bootstrap.sql` for an optional SQL bootstrap (creates tables + sample content). Useful for quick local seeds but not required for tests.
- Tests run with in-memory SQLite (see `phpunit.xml`) so unit/feature tests don't require an external DB. Use `composer test` or `php artisan test`.

Project-specific patterns to follow
- When adding public-facing content, prefer using the `[form slug="..."]` shortcode instead of embedding raw controllers in views.
- Keep admin-facing routes/resources stable (`/admin` landing must not 500 even if internal views are moved). Use redirects in the admin landing route.
- When adding modules, follow the `module.json` structure and provide a `provider` class that can be safely registered by `ModuleManager`.

Files to inspect for further context
- [config/cms.php](config/cms.php)
- [app/Support/Cms.php](app/Support/Cms.php)
- [app/Support/ModuleManager.php](app/Support/ModuleManager.php)
- [app/Providers/CmsServiceProvider.php](app/Providers/CmsServiceProvider.php)
- [routes/web.php](routes/web.php)
- `impartcms_bootstrap.sql` (SQL bootstrap)

What to ask the maintainer next
- Confirm whether `cms.allow_raw_html` should default to `true` in production.
- Confirm preferred module layout (namespacing convention) for new modules placed in `modules/`.

If this file is incomplete or you want examples added (module.json sample, blade snippet examples), tell me which examples to include and I will iterate.
