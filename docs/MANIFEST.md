# ImpartCMS – Current State Manifest (Laravel 12 + Breeze)

## Repo
- GitHub: https://github.com/Loukie/impartcms
- Local path: `C:\laragon\www\2kocms`
- Local domain: http://2kocms.test (Laragon)

---

## Core Goal
- A custom CMS (WordPress-style) focused on **security, speed, and modularity**.
- Current milestone includes:
  - Pages admin with Draft/Publish workflow
  - Admin-only preview
  - Trash system (soft deletes)
  - Fully functional SEO output layer (incl. OG/Twitter)
  - WordPress-style admin sidebar
  - Basic Settings module (site name + logo)

---

## Authentication / Admin Access
- Laravel Breeze installed (Blade auth scaffolding).
- Admin access controlled via Gate:
  - File: `app/Providers/AppServiceProvider.php`
  - Gate name: `access-admin`
  - Middleware usage: `auth + can:access-admin`

---

## Routing (Critical)
- File: `routes/web.php`
- Admin routes grouped under prefix:
  - `config('cms.admin_path', 'admin')` → default `/admin`
- Public pages use catch-all route at bottom:
  - `GET /{slug} → PageController@show` (**must remain LAST**)
- Admin-only preview route:
  - `GET /_preview/pages/{pagePreview}`
  - Route name: `pages.preview`
  - Middleware: `auth + can:access-admin`
  - Supports drafts AND trashed pages
- Admin Settings routes:
  - `GET  /admin/settings → admin.settings.edit`
  - `PUT  /admin/settings → admin.settings.update`
  - Protected by admin middleware group

---

## Route Model Binding (Trash + Preview Safety)
- Custom bindings in `routes/web.php`:
  - `pagePreview → Page::withTrashed()`
  - `pageTrash → Page::withTrashed()`
- Prevents 404s when previewing, restoring, or force-deleting trashed pages.

---

## Public Rendering
- Controller: `app/Http/Controllers/PageController.php`
- `show()`:
  - Serves **published pages only**
  - Homepage uses `is_homepage` flag
  - Trashed pages never render publicly
- `preview()`:
  - Renders draft/published/trashed pages for admins only
  - Adds response header:
    - `X-Robots-Tag: noindex, nofollow`

---

## Pages Admin (CRUD + Draft/Publish)
- Controller: `app/Http/Controllers/Admin/PageAdminController.php`
- Draft/Publish controlled via form submit buttons:
  - `name="action"` → `draft` or `publish`
- Behaviour:
  - Draft → `status=draft` (never public)
  - Publish → `status=published` + `published_at`
- SEO is saved/updated alongside the page.

---

## Trash System (WordPress-style)
- Uses Soft Deletes on pages:
  - Migration: `*_add_deleted_at_to_pages_table.php`
  - Model: `app/Models/Page.php` uses `SoftDeletes`
- Admin Trash routes:
  - `GET    /admin/pages-trash`
  - `POST   /admin/pages-trash/{pageTrash}/restore`
  - `DELETE /admin/pages-trash/{pageTrash}/force`
- Behaviour:
  - Trash → soft delete
  - Restore → restore record
  - Delete permanently → force delete

---

## SEO Relation
- Page model relates to SEO meta via `SeoMeta` model:
  - File: `app/Models/Page.php`
  - Relation: `seo() → hasOne(SeoMeta::class)`
- Force deleting a page deletes the related SEO record.

---

## SEO Output Layer (Now Functional)
- Theme file: `resources/views/themes/default/page.blade.php`
- SEO tags rendered in `<head>` with fallbacks:
  - `<title>`
  - `<meta name="description">`
  - `<link rel="canonical">`
  - `<meta name="robots">`
  - OpenGraph tags (`og:*`)
  - Twitter tags (`twitter:*`)
- Preview routes remain protected via `X-Robots-Tag: noindex, nofollow`.

---

## Admin Views (Blade)

### Pages List
- File: `resources/views/admin/pages/index.blade.php`
- Header actions:
  - Trash
  - New Page
- Per-row actions:
  - View Live (published)
  - Preview Draft (draft)
  - Edit
  - Trash

### Trash List
- File: `resources/views/admin/pages/trash.blade.php`
- Actions:
  - Preview
  - Restore
  - Delete permanently

### Page Create/Edit (RankMath-style UI)
- Files:
  - `resources/views/admin/pages/create.blade.php`
  - `resources/views/admin/pages/edit.blade.php`
- Layout:
  - 2-column editor
    - Content left
    - Sidebar right (sticky)
- Sidebar panels:
  - Status/actions
  - SEO panel (tabs):
    - General
    - Social
    - Advanced
  - Live SERP preview
  - Simple SEO score + checklist (JS-only)

### Navigation Cleanup
- Removed duplicate inline “mini admin nav” (Pages / Trash / Forms / Settings)
- Navigation is handled exclusively by the global sidebar.

---

## Admin Sidebar (WordPress-style)
- Rendered only on admin routes and for admin users.
- Controlled in: `resources/views/layouts/app.blade.php`
- Conditions:
  - Admin path match
  - `Gate::allows('access-admin')`
- Sidebar partial:
  - File: `resources/views/admin/partials/sidebar.blade.php`
  - Links:
    - Pages
    - Trash
    - Settings
    - Forms (soon placeholder)
  - Displays Site Name + optional Logo.

---

## Settings Module (Basic v1)
- Purpose:
  - Manage Site Name and Logo via admin UI.
- Storage:
  - Table: `settings` (key/value)
  - Migration: `*_create_settings_table.php`
- Model:
  - File: `app/Models/Setting.php`
  - Cached per key
- Controller:
  - File: `app/Http/Controllers/Admin/SettingsController.php`
  - Handles load, validation, upload, removal
- View:
  - File: `resources/views/admin/settings/edit.blade.php`
- Layout integration:
  - `<title>` uses saved Site Name
  - Sidebar shows logo + site name if set

---

## Guarantees (Security + Behaviour)
- Draft pages are never publicly routable.
- Trashed pages are never publicly routable.
- Only admins can preview drafts/trashed pages.
- Preview responses are always noindex/nofollow.
- Settings screen is admin-only.
- Secrets excluded from Git:
  - `.env`
  - `/vendor`
  - `/node_modules`

---

## Setup Commands (Fresh Install)
```bash
composer install
npm install
npm run build
php artisan migrate
php artisan storage:link
php artisan optimize:clear
