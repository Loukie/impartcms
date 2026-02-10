# ImpartCMS – Current State Manifest (Laravel 12 + Breeze)

Repo
- GitHub: https://github.com/Loukie/impartcms
- Local path: C:\laragon\www\2kocms
- Local domain: http://2kocms.test (Laragon)

Core Goal
- A custom CMS (WordPress-style) focused on security, speed, modularity.
- Current milestone: Pages admin with Draft/Publish workflow, admin-only preview, and Trash system (soft deletes).

Authentication / Admin Access
- Laravel Breeze installed (Blade auth scaffolding).
- Admin access controlled via Gate:
  - File: app/Providers/AppServiceProvider.php
  - Gate name: access-admin
  - Middleware usage: auth + can:access-admin

Routing (Critical)
- File: routes/web.php
- Admin routes grouped under prefix:
  - config('cms.admin_path', 'admin') => default /admin
- Public pages use catch-all route at bottom:
  - GET /{slug} => PageController@show (must remain LAST)
- Admin-only preview route:
  - GET /_preview/pages/{pagePreview}
  - Route name: pages.preview
  - Protected by middleware: auth + can:access-admin
  - Preview supports drafts AND trashed pages.

Route Model Binding (Important for Trash + Preview)
- routes/web.php defines custom bindings:
  - pagePreview => resolves Page::withTrashed()
  - pageTrash   => resolves Page::withTrashed()
- This prevents 404 when restoring/force deleting trashed pages and allows previewing trashed pages.

Public Rendering
- Controller: app/Http/Controllers/PageController.php
- show():
  - Only serves status = published pages publicly
  - Homepage uses is_homepage flag
  - Trashed pages are never public (SoftDeletes excludes them by default)
- preview():
  - Renders any page (draft/published/trashed) for admins only
  - Adds header: X-Robots-Tag: noindex, nofollow

Pages Admin (CRUD + Draft/Publish)
- Controller: app/Http/Controllers/Admin/PageAdminController.php
- Draft/Publish controlled via form submit buttons:
  - name="action" value="draft" or "publish"
- Behavior:
  - Draft: status=draft (never public)
  - Publish: status=published (+ published_at)
- SEO saved/updated alongside page.

Trash System (WordPress-style)
- Uses Soft Deletes on pages:
  - Migration: database/migrations/*_add_deleted_at_to_pages_table.php
  - Model: app/Models/Page.php uses SoftDeletes
- Admin Trash routes:
  - GET    /admin/pages-trash                     => PageAdminController@trash (admin.pages.trash)
  - POST   /admin/pages-trash/{pageTrash}/restore => PageAdminController@restore (admin.pages.restore)
  - DELETE /admin/pages-trash/{pageTrash}/force   => PageAdminController@forceDestroy (admin.pages.forceDestroy)
- Behavior:
  - "Trash" action = soft delete (move to trash)
  - Restore = restore soft deleted record
  - Delete permanently = force delete

SEO Relation (Important)
- Page model relates to SEO meta using SeoMeta model (table: seo_meta)
  - File: app/Models/Page.php
  - Method: seo() => hasOne(SeoMeta::class)
- Force deleting a page deletes the related SEO record.

Admin Views (Blade)
- Pages list:
  - File: resources/views/admin/pages/index.blade.php
  - Has header buttons: "Trash" + "New Page"
  - Per-row actions:
    - Published: View Live
    - Draft: Preview Draft (admin-only)
    - Edit
    - Trash (Move to Trash)
- Trash list:
  - File: resources/views/admin/pages/trash.blade.php
  - Per-row actions:
    - Preview (admin-only)
    - Restore
    - Delete Permanently
- Page create/edit screens:
  - Files:
    - resources/views/admin/pages/create.blade.php
    - resources/views/admin/pages/edit.blade.php
  - Include:
    - Cancel link
    - Save Draft + Publish buttons
    - Preview Draft / View Live link in header (depending on status)

Guarantees (Security + Behaviour)
- Draft pages: NOT publicly reachable via slug routes.
- Trashed pages: NOT publicly reachable via slug routes.
- Only admins can preview drafts/trashed pages via /_preview/pages/{id}.
- Preview responses are noindex/nofollow to avoid indexing.
- Dependencies and secrets excluded from Git:
  - .env, /vendor, /node_modules ignored via .gitignore

Setup Commands (fresh install)
- composer install
- npm install
- npm run build
- php artisan migrate
- php artisan optimize:clear

Current Next Features (planned)
- Forms module v1:
  - Shortcode placement (header/footer/body)
  - Per-form recipient routing by page/user
- Modules/Plugin system:
  - Enable/disable features separate from core
- Roles/permissions beyond admin (editor, author)
