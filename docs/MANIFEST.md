# ImpartCMS – Current State Manifest (Laravel 12 + Breeze)

Repo
- GitHub: https://github.com/Loukie/impartcms
- Local path: C:\laragon\www\2kocms
- Local domain (Laragon): http://2kocms.test

Core Goal
- Custom CMS (WordPress-style) focused on security, speed, modularity.
- Current milestone: Pages admin with Draft/Publish workflow, admin-only preview, Trash (soft deletes), and a consistent admin sidebar layout.

---

## Auth + Admin Access Control
- Laravel Breeze installed (Blade auth scaffolding).
- Admin access gate:
  - File: `app/Providers/AppServiceProvider.php`
  - Gate: `access-admin`
- Admin routes protected by middleware:
  - `auth` + `can:access-admin`

---

## Routing (critical)
File: `routes/web.php`

Public
- Catch-all page route must remain last:
  - `GET /{slug}` → `PageController@show`
- Homepage is resolved via `is_homepage` flag (published only).

Admin-only preview (drafts + trashed)
- `GET /_preview/pages/{pagePreview}` → `PageController@preview`
- Route name: `pages.preview`
- Middleware: `auth` + `can:access-admin`
- Adds `X-Robots-Tag: noindex, nofollow`

Trash routes (admin)
- `GET    /admin/pages-trash` → `PageAdminController@trash` (name: `admin.pages.trash`)
- `POST   /admin/pages-trash/{pageTrash}/restore` → `PageAdminController@restore` (name: `admin.pages.restore`)
- `DELETE /admin/pages-trash/{pageTrash}/force` → `PageAdminController@forceDestroy` (name: `admin.pages.forceDestroy`)

Route model binding (important)
- `pagePreview` resolves with `Page::withTrashed()` (enables preview of trashed pages)
- `pageTrash` resolves with `Page::withTrashed()` (prevents 404 on restore/force delete)

Admin prefix
- Admin routes under prefix: `config('cms.admin_path', 'admin')` (default `/admin`)

---

## Public Page Rendering
File: `app/Http/Controllers/PageController.php`

- `show()`:
  - Public can only see `status = published` pages.
  - Draft pages are never public.
  - Trashed pages are never public (SoftDeletes excluded).
  - Loads SEO relation (`$page->load('seo')`) and passes it to the theme view.
- `preview()`:
  - Admin-only rendering for drafts + trashed pages.
  - Loads SEO relation and returns `noindex, nofollow`.

---

## Pages Admin (CRUD + Draft workflow)
File: `app/Http/Controllers/Admin/PageAdminController.php`

- CRUD routes via resource controller.
- Draft/Publish handled via submit action:
  - `request('action')` = `draft` or `publish`
- Behaviour:
  - Draft: `status = draft`, `published_at` cleared (drafts never public)
  - Publish: `status = published`, sets `published_at` if missing
- `destroy()` moves page to Trash (soft delete).
- `trash()` lists trashed pages.
- `restore()` restores trashed pages.
- `forceDestroy()` permanently deletes pages.

⚠️ Important fix applied
- Edit page UI previously could “delete” when clicking update due to invalid nested forms.
- Views were corrected so update actions submit the correct form (no accidental deletes).

---

## Trash System (WordPress-style)
Database
- Migration: `database/migrations/*_add_deleted_at_to_pages_table.php`
  - Adds `deleted_at` (SoftDeletes) to pages.

Model
- File: `app/Models/Page.php`
  - Uses `SoftDeletes`
  - Deletes SEO record on force delete (prevents orphan SEO rows)

Rules
- Trashed pages: never public.
- Admin can preview trashed pages via the preview route.

---

## SEO Meta (data layer)
- SEO fields are stored per page in `seo_meta`.
- Page model relation:
  - File: `app/Models/Page.php`
  - `seo()` → `hasOne(SeoMeta::class)`
- Admin create/update saves SEO alongside page:
  - File: `app/Http/Controllers/Admin/PageAdminController.php`
  - `validatedSeo()` defines SEO field validation (meta title/description, canonical, robots, OG, Twitter).

⚠️ SEO output status
- Data is saved + loaded and is available in views (`$page->seo`), including preview.
- Final “SEO functioning” requires meta tags to be printed in the theme `<head>` layout (needs wiring in theme layout/partial if not already done).

---

## Admin UI / Views
Pages list
- `resources/views/admin/pages/index.blade.php`
  - Actions: View Live (published), Preview Draft (draft), Edit, Trash (soft delete)
  - Header buttons: New Page + Trash listing access
  - Shows created/updated timestamps (requested enhancement)

Trash list
- `resources/views/admin/pages/trash.blade.php`
  - Actions: Preview (admin-only), Restore, Delete Permanently
  - Shows deleted timestamp + created/updated (requested enhancement)

Create/Edit
- `resources/views/admin/pages/create.blade.php`
- `resources/views/admin/pages/edit.blade.php`
  - Save Draft / Publish (new) and Update/Go Live (existing page UX)
  - Cancel link
  - No nested forms (prevents accidental deletes)

---

## Admin Layout / Sidebar (always visible)
Goal: Admin sidebar must show on all backend screens (dashboard + pages + settings etc.)

Real layout view
- `resources/views/layouts/admin.blade.php`
  - Sidebar includes:
    - Dashboard
    - View site (under Dashboard)
    - Pages
    - Settings
  - Sidebar excludes:
    - Trash (removed per request)
  - Top bar includes:
    - Logged-in user
    - Log out
  - Top bar excludes:
    - View site (moved to sidebar)

Component forwarding (critical fix to avoid “no changes showing”)
- `resources/views/components/layouts/admin.blade.php`
  - Forces `<x-layouts.admin>` to render the real layout `layouts.admin`
- `resources/views/components/admin-layout.blade.php`
  - `<x-admin-layout>` wrapper around `<x-layouts.admin>`

Why this matters
- Laravel resolves `<x-layouts.admin>` from `resources/views/components/layouts/admin.blade.php`.
- If you edit `resources/views/layouts/admin.blade.php` but the component layout exists, UI may not update unless forwarded correctly.
- This forwarding fix ensures only one layout is the source of truth.

Settings link visibility
- Sidebar “Settings” link expects a named route `admin.settings.edit` (or the sidebar checks route existence using `Route::has`).
- If Settings route name differs, update the sidebar link accordingly.

---

## Git hygiene
- `.env`, `/vendor`, `/node_modules`, `/public/build` excluded via `.gitignore`.
- Use clear commit messages before major changes.

---

## Setup (fresh install)
- `composer install`
- `npm install`
- `npm run build`
- `php artisan migrate`
- `php artisan optimize:clear`

---

## Next planned features
- Wire SEO meta output into theme `<head>` (title, description, canonical, robots, OG, Twitter).
- Forms module v1 (shortcodes + per-form recipient routing by page/user).
- Modules/plugin system (enable/disable features separate from core).
- Roles/permissions beyond admin (editor/author).
