# ImpartCMS – Complete Project History

**Last Updated**: 2026-03-02 (Africa/Johannesburg, SAST)

---

## Overview

This document tracks all major development work on ImpartCMS, a Laravel 12 CMS overlay with AI-powered page generation, bulk administration, and extensible module architecture.

---

# Part 1: Foundation & Core Systems

## 2026-02-10 – Initial Laravel + Breeze Setup

**What was done**
- Fresh Laravel 12 install + Breeze auth scaffolding
- MySQL database configuration + migrations
- Git repository initialized (private)

**Why**
- Foundation for custom CMS

**Files**
- `bootstrap/`, `config/`, `app/`, `database/`

**Resolved** ✅
- Working authentication (login/register/dashboard)
- Stable database structure

---

## 2026-02-11 – Pages System v1 (Core CMS Feature)

**What was done**
- Full CRUD for pages with slug-based routing
- Draft/Published status with `published_at` timestamps
- Homepage flag (`is_homepage`) with protected deletion
- SEO meta table relation + preview-protected routes
- Soft delete (trash) system for all pages
- Admin-only draft preview with `X-Robots-Tag: noindex`

**Why**
- Pages are the core CMS feature; trash prevents accidental deletion

**Files created/changed**
- `app/Models/Page.php`
- `app/Http/Controllers/Admin/PageAdminController.php`
- `app/Http/Controllers/PageController.php`
- `database/migrations/*_create_pages_table.php`
- `database/migrations/*_create_seo_meta_table.php`
- `resources/views/admin/pages/*`

**Resolved** ✅
- Create/edit/delete/restore pages
- Public page rendering
- Draft pages not publicly accessible
- SEO metadata storage

**Not resolved** ❌
- SEO output not yet rendered in `<head>` (added later)

---

## 2026-02-11 – Admin Sidebar & Layout Standardization

**What was done**
- Built custom admin layout with persistent left sidebar
- Unified navigation across all admin screens
- Sidebar includes: Dashboard, Pages, Trash, Settings, Forms, Media, Users
- Responsive grid layout with sidebar always visible on desktop

**Why**
- Admin navigation should be centralized and consistent (WordPress-style)

**Files**
- `resources/views/layouts/admin.blade.php`
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/components/admin-layout.blade.php`
- `resources/views/admin/partials/sidebar.blade.php`

**Resolved** ✅
- Unified admin navigation
- Consistent layout across backend

---

## 2026-02-12 – RankMath-Style Page Editor (2-Column Layout)

**What was done**
- Converted single-column editor to 2-column layout:
  - Left: content (title, slug, body)
  - Right: sidebar panels (Status, SEO, Advanced)
- Added live SERP preview + basic SEO checklist (JS-only)
- Preserved all form input names (no backend changes)

**Why**
- Improve editorial clarity and SEO visibility

**Files**
- `resources/views/admin/pages/create.blade.php`
- `resources/views/admin/pages/edit.blade.php`

**Resolved** ✅
- Cleaner content/SEO separation
- Better UX for editors

---

## 2026-02-12 – Settings Module v1 (Branding)

**What was done**
- Created `settings` table (key/value storage)
- Added Settings admin page with:
  - Site Name
  - Logo upload/removal
  - Favicon selection
  - Maintenance mode toggle
- Updated layouts to use configurable site name + logo + favicon

**Why**
- Site identity should not be hardcoded
- Foundation for future global settings

**Files created**
- `database/migrations/*_create_settings_table.php`
- `app/Models/Setting.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `resources/views/admin/settings/edit.blade.php`

**Resolved** ✅
- Site name, logo, favicon all configurable
- Branding consistent across admin & public frontend

**Not resolved**
- Advanced global SEO defaults (later work)

---

## 2026-02-13 – Media Manager (Upload + Organize + Detail View)

**What was done**
- Introduced Media manager with:
  - Image + PDF upload
  - Auto-organize into YYYY/MM folders
  - View/edit metadata (title, alt, caption)
  - Show "Where used" (initial detection)
  - Media picker modal for embed selection
- Optimized Media list with filtering, search, thumbnails

**Why**
- Professional CMS requires asset management

**Files**
- `app/Http/Controllers/Admin/MediaAdminController.php`
- `app/Models/Media.php` (or `MediaFile.php`)
- `database/migrations/*_create_media_files_table.php`
- `resources/views/admin/media/*`
- `resources/views/components/admin/media-picker.blade.php`

**Resolved** ✅
- Upload, browse, organize media
- Media picker modal for forms/pages/settings

**Known issue** ⚠️
- "Where used" detection depends on correct `APP_URL`

---

## 2026-02-13 – Users Administration

**What was done**
- Added Users admin section:
  - List users + show admin status
  - Create users (with password generation / reset flow)
  - Toggle admin role
  - Soft-delete (trash) system with restore/permanent delete
- Added safety rules:
  - Can't trash yourself
  - Can't trash last admin
  - Can't permanently delete last active admin

**Why**
- CMS needs user management; trash prevents accidental data loss

**Files**
- `app/Http/Controllers/Admin/UserAdminController.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/trash.blade.php`
- `database/migrations/*_add_deleted_at_to_users_table.php`

**Resolved** ✅
- Full user CRUD + trash flow

---

# Part 2: SEO, Icons & Content Rendering

## 2026-02-18 – SEO Output Layer Activation

**What was done**
- Implemented full SEO rendering in `<head>`:
  - `<title>`, meta description, canonical, robots
  - OpenGraph tags (og:title, og:description, og:image, etc.)
  - Twitter Card tags
- Added safe fallbacks (title → page title → app name, etc.)

**Why**
- SEO fields were saved but not rendered; this broke Google/social previews

**Files**
- `resources/views/themes/default/page.blade.php`
- `resources/views/themes/default/page-blank.blade.php`
- `app/Http/Controllers/PageController.php`

**Resolved** ✅
- SEO meta now functional
- Social sharing previews work
- Canonical + robots always present

---

## 2026-02-18 – Icon System (Font Awesome + Lucide)

**What was done**
- Rebuilt Font Awesome icon generator to use SVG files directly (vs. broken metadata imports)
- Icon library renders Font Awesome **inline SVG** (portable, works everywhere)
- Added per-icon **Copy Shortcode** button
- Icons now support both **Copy** and **Select** modes
- Integrated icon shortcodes: compact format `[icon kind="fa" value="fa-solid fa-house" size="24" colour="#4CBB17"]`

**Why**
- Font Awesome metadata import was failing Vite builds
- SVG rendering is portable (no font-loading issues)
- Icon selection should be as easy as searching Font Awesome

**Files created**
- `scripts/generate-fa-icons.mjs`
- `app/Support/IconRenderer.php`

**Files changed**
- `resources/js/admin/fa-icons.json` (regenerated)
- `resources/js/admin/fa-icon-list.js`
- `resources/js/admin/icon-library.js`
- `resources/views/components/admin/icon-picker.blade.php`
- `app/Support/Cms.php`
- `config/cms.php`

**Resolved** ✅
- Font Awesome icons render reliably
- Icon search works
- Vite build succeeds
- Icon shortcodes portable and human-readable

---

## 2026-02-18 – Settings: Brand Logos & Favicons (Image OR Icon)

**What was done**
- Settings now allow selecting logo/favicon as **Image** or **Icon**:
  - Main site logo (media/icon)
  - Favicon (media/icon via `/favicon.svg` route)
  - Login page logo (media/icon with configurable size)
- Unified picker component: one button opens Media Library (choose image or icon)
- Shortcode copy button for selected icons

**Why**
- Flexibility: some prefer icons for brand marks, others prefer uploaded images
- Single picker = less UI clutter

**Files created**
- `app/Http/Controllers/FaviconController.php`
- `resources/views/components/admin/media-icon-picker.blade.php`

**Files changed**
- `resources/views/admin/settings/edit.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/admin/partials/sidebar.blade.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `routes/web.php`

**Resolved** ✅
- Logo + favicon fully configurable
- Both frontend and admin consistent
- Login page branding independently configured

---

## 2026-02-20 – Page Rendering: HTML Pass-Through + Shortcode Parsing

**What was done**
- Pages now render HTML in body (not escaped) when `allow_raw_html` is enabled
- Shortcodes now parse within full HTML documents (e.g., pages saved as full `<!doctype>` HTML)
- Notice bar styling applied to full HTML pages dynamically
- Frontend favicon tags injected + cache-busting added

**Why**
- Users may paste full HTML snippets; they should render
- Shortcodes must work everywhere (partial pages, full HTML, plain text)
- Favicon changes should be visible immediately (not cached)

**Files**
- `app/Support/Cms.php`
- `app/Http/Controllers/PageController.php`
- `config/cms.php`

**Resolved** ✅
- Full HTML pages supported with shortcode parsing
- [form ...] and [icon ...] work in all contexts

---

# Part 3: Forms System

## 2026-02-16 – Forms Module v1 (CRUD + Submissions + Delivery)

**What was done**
- Created Forms admin section (list, create, edit)
- Form builder: JSON schema for field definitions
- Submissions captured in database + deliverable to email
- Form shortcode: `[form slug="contact"]` for frontend embed
- Delivery logging: store status + error messages

**Why**
- Core CMS feature; many sites need forms

**Files created**
- `app/Http/Controllers/Admin/FormAdminController.php`
- `app/Http/Controllers/Admin/FormSettingsAdminController.php`
- `app/Http/Controllers/FormSubmissionController.php`
- `app/Models/Form.php`
- `app/Models/FormSubmission.php`
- `database/migrations/*_create_forms_table.php`
- `database/migrations/*_create_form_submissions_table.php`
- `database/migrations/*_create_form_recipient_rules_table.php`
- `resources/views/cms/forms/embed.blade.php`

**Resolved** ✅
- Forms CRUD + embedding
- Submissions stored
- Email delivery works

---

## 2026-02-16 – Forms Builder (Drag-Drop UI)

**What was done**
- WPForms-style builder:
  - Palette of field types (text, textarea, select, etc.)
  - Drag to canvas
  - Reorder fields
  - Edit field labels, requirements, options
- Front-end embed renders fields + submission handling

**Why**
- Admin should not edit JSON manually

**Files**
- `resources/js/admin/forms-builder.js`
- `resources/views/admin/forms/edit.blade.php`
- `resources/views/cms/forms/embed.blade.php`

**Resolved** ✅
- Drag-drop builder working
- Form embeds render correctly

**Known issues** ⚠️
- Page break add/remove stability
- Focus jumping in option editor

---

## 2026-02-18 – Forms: Trash System + SMTP/Brevo Email

**What was done**
- Forms now use soft-delete (trash system)
- Added Forms trash view (restore + permanent delete)
- Restored **SMTP settings** and **Brevo API** option for form delivery
- Email provider selection: `env` (Laravel default), `smtp` (override), `brevo` (API)
- Secrets encrypted at rest (using Laravel Crypt)

**Why**
- Forms should be trash-protected like other content
- Organizations need flexible email delivery options

**Files created**
- `app/Support/FormMailer.php`

**Files changed**
- `app/Models/Form.php` (added SoftDeletes)
- `app/Http/Controllers/Admin/FormAdminController.php` (trash methods)
- `app/Http/Controllers/Admin/FormSettingsAdminController.php` (SMTP/Brevo)
- `app/Models/Setting.php` (added `setSecret/getSecret` for encrypted storage)
- `resources/views/admin/forms/trash.blade.php` (new)
- `database/migrations/2026_02_18_170000_add_deleted_at_to_forms_table.php`

**Resolved** ✅
- Forms trash flow complete
- Multiple email providers supported

---

## 2026-02-18 – Forms: CC/BCC Support (Global + Per-Embed)

**What was done**
- Forms Settings: added default **CC** and **BCC** (CSV)
- Form shortcode now accepts overrides: `[form slug="contact" cc="..." bcc="..."]`
- CC/BCC applied for all providers (env/smtp/brevo)

**Why**
- Organizations need multiple recipients for compliance/audit trails

**Files**
- `app/Http/Controllers/Admin/FormSettingsAdminController.php`
- `app/Support/Cms.php`
- `app/Http/Controllers/FormSubmissionController.php`
- `app/Support/FormMailer.php`
- `resources/views/cms/forms/embed.blade.php`

**Resolved** ✅
- CC/BCC working across all providers

---

# Part 4: Page Customization & Branding

## 2026-02-18 – Homepage Selection & Maintenance Mode

**What was done**
- Homepage dropdown shows only page title (not slug/ID)
- Maintenance mode added to Settings:
  - Enable/disable checkbox
  - Select maintenance landing page
  - All public GET/HEAD requests redirect to it
  - Admin users bypass redirect
  - Static assets + auth routes unaffected

**Why**
- Cleaner UX for homepage selection
- CMS should support maintenance windows (WordPress-style)

**Files**
- `app/Http/Middleware/CmsMaintenanceMode.php` (new)
- `app/Http/Controllers/Admin/SettingsController.php`
- `bootstrap/app.php`
- `resources/views/admin/settings/edit.blade.php`

**Resolved** ✅
- Homepage selection polished
- Maintenance mode fully functional

---

# Part 5: AI Integration (Latest Work)

## 2026-03-02 – AI Provider Integration & Page Generation

**What was done**
- **AI Agent Settings** (Admin → Settings → AI Agent):
  - Select provider: OpenAI / Gemini / Disabled
  - Store API keys encrypted
  - Timeout configuration (0 unlimited, cap 600s)
  - Model selector (dropdown with "free-tier available" hints)

- **AI Page Generation** (Admin → Pages → AI Page):
  - Generate page with sanitized HTML
  - Save as draft by default
  - Unset homepage automatically

- **AI Site Builder** (Admin → Pages → AI Site Builder):
  - Generate JSON blueprint (sitemap + page briefs + SEO)
  - Bulk-create pages from blueprint
  - Inject HTML per page
  - Slug uniqueness + draft safety

- **Gemini Integration**:
  - Text generation (`GeminiGenerateContentClient`)
  - Vision redesign (`GeminiVisionClient`)

- **Global AI Popup** ("AI ✨"):
  - Available on all admin screens
  - Page search + context auto-detect
  - Tweak/rewrite modes
  - Revision backup before overwrite

- **AI Visual Audit**:
  - Screenshot capture (PHP-only, Chrome/Edge headless)
  - Gemini vision redesign
  - Save redesigned HTML as draft

- **Homepage Safeguards**:
  - Homepage only on published pages
  - Unset Home per page
  - Clear Home global action
  - AI flows unset homepage (avoid draft homepages)

**Files created**
- `app/Http/Controllers/Admin/AiAgentSettingsController.php`
- `app/Http/Controllers/Admin/AiPageAssistAdminController.php`
- `app/Http/Controllers/Admin/AiVisualAuditAdminController.php`
- `app/Models/PageRevision.php`
- `app/Support/Ai/AiSiteBlueprintGenerator.php`
- `app/Support/Ai/AiSiteBuilder.php`
- `app/Support/Ai/AiVisualRedesigner.php`
- `app/Support/Ai/GeminiVisionClient.php`
- `app/Support/Ai/VisionClientInterface.php`
- `app/Support/Ai/NullVisionClient.php`
- `database/migrations/2026_03_02_114500_create_page_revisions_table.php`
- `resources/views/admin/pages/ai-create.blade.php`
- `resources/views/admin/pages/ai-site-builder.blade.php`
- `resources/views/admin/settings/ai-agent.blade.php`
- `resources/views/admin/partials/ai-popup.blade.php`
- `resources/views/admin/ai/visual-audit.blade.php`
- `resources/js/admin/ai-popup.js`
- `docs/AI_VISUAL_AUDIT.md`

**Files edited (key)**
- `app/Providers/AppServiceProvider.php` (bind provider-selected LLM + Gemini vision)
- `routes/web.php` (AI endpoints)
- `resources/views/layouts/admin.blade.php` (sidebar + AI popup)
- `resources/views/admin/pages/index.blade.php` (homepage toggle, Clear Home)
- `app/Http/Controllers/Admin/PageAdminController.php` (homepage set/unset/clear)
- `app/Http/Controllers/PageController.php` (signed preview for screenshots)
- `app/Support/Ai/AiPageGenerator.php` (existing)
- `app/Support/Ai/HtmlSanitiser.php` (existing)

**Operational notes**
- Run `php artisan migrate` after apply
- Screenshot capture requires Chrome/Edge; optional override: `AI_SCREENSHOT_BIN`
- API keys stored encrypted; timeout defaults to 600s max

**Resolved** ✅
- Complete AI workflow: provider selection → page generation → site builder → visual redesign
- Multiple LLM providers supported (OpenAI + Gemini + fallback)
- Homepage protection throughout AI flows
- Revision backups for safety

---

# Part 6: Bulk Admin Actions

## 2026-03-02 – Bulk Delete/Trash for All List Pages

**What was done**
- Added **bulk trash** capability to:
  - Pages list (skip homepage)
  - Forms list
  - Users list (skip self + last admin)
  - Media
  - Layout blocks (header/footer)
  - Custom snippets

- Added **bulk permanent delete** for:
  - Trashed pages (skip homepage)
  - Trashed forms
  - Trashed users (skip last active admin)

**Implementation**
- Checkboxes on all index views
- "Select All" header checkbox
- "Trash Selected" / "Delete Selected" button (disabled until items checked)
- Bulk methods added to each controller
- New routes: `admin.{resource}.bulk` + `admin.{resource}.trash.bulk`
- Feature tests covering bulk operations + safety rules

**Why**
- Admins need fast bulk operations (trash many items at once)
- Safety: homepage + last admin always protected
- Consistent UX across all resource types

**Files created**
- `tests/Feature/BulkActionsTest.php` (comprehensive test suite)

**Files edited**
- `app/Http/Controllers/Admin/PageAdminController.php` (bulk, bulkForceDestroy)
- `app/Http/Controllers/Admin/FormAdminController.php` (bulk)
- `app/Http/Controllers/Admin/UserAdminController.php` (bulk)
- `app/Http/Controllers/Admin/MediaAdminController.php` (bulk)
- `app/Http/Controllers/Admin/LayoutBlockAdminController.php` (bulk)
- `app/Http/Controllers/Admin/CustomSnippetAdminController.php` (bulk)
- `resources/views/admin/pages/index.blade.php` (bulk UI + JS)
- `resources/views/admin/pages/trash.blade.php` (bulk delete UI + JS)
- `resources/views/admin/forms/index.blade.php` (bulk UI)
- `resources/views/admin/users/index.blade.php` (bulk UI)
- `resources/views/admin/media/index.blade.php` (bulk UI)
- `resources/views/admin/layout-blocks/index.blade.php` (bulk UI)
- `resources/views/admin/custom-snippets/index.blade.php` (bulk UI)
- `routes/web.php` (bulk routes)

**Resolved** ✅
- Full bulk action workflow on all list pages
- Bulk permanent delete for trash pages
- Safety rules enforced (homepage, last admin)
- Tests covering all scenarios

---

## 2026-03-02 – Database & Seeder Fixes

**What was done**
- Fixed migration compatibility issues across SQLite (tests) and MySQL (dev):
  - Guarded `SHOW` query in migrations (SQLite doesn't support it)
  - Fixed `ALTER TABLE ... ADD ... NOT NULL` workflow (update rows before adding NOT NULL constraint)

- Updated `CmsStarterSeeder` to create admin user if none exists:
  - Admin credentials: `lourens@2ko.co.za` / `L0ur3nsn3l2630`
  - Idempotent (safe to re-run)
  - Ensures test environment has predictable auth

**Why**
- Support for both SQLite + MySQL environments (test + dev)
- Admin access recovery after migrations/resets

**Files changed**
- `database/migrations/2026_02_10_100755_make_pages_published_at_nullable.php`
- `database/seeders/CmsStarterSeeder.php`
- `tests/Feature/BulkActionsTest.php` (updated helper to use fixed credentials)

**Resolved** ✅
- Migrations run cleanly on both SQLite and MySQL
- Admin user automatically ensured after seed

---

## 2026-03-02 – AI Client Syntax Fix

**What was done**
- Fixed ParseError in `OpenAiResponsesClient::generateText()`:
  - Removed stray duplicated request chaining (`->withToken/->post` fragment)
  - Caused "unexpected token →" error on app boot

**Why**
- Parse error blocked site-builder and other AI features

**Files**
- `app/Support/Ai/OpenAiResponsesClient.php`

**Resolved** ✅
- App boots without ParseError
- AI endpoints accessible

---

# Part 7: Admin UX Polish

## 2026-03-02 – Various Admin UI Improvements

**What was done**
- Added **word count** + **slug preview** to page editor (live JS)
- Expanded **provider sections toggle** in AI Agent settings
- Added **PRG (Post/Redirect/Get)** to site-builder to prevent 404 on reload
- Enhanced **timeout support**: unlimited option (0), cap 600s
- Added **bulk delete for trash pages** (not just soft-delete in main list)
- Consistent **bulk action styling** across all list pages

**Why**
- Better UX feedback for editors
- Prevent accidental form re-posting
- Flexible timeout configuration for long-running AI requests

**Files edited**
- `resources/views/admin/pages/create.blade.php` (word count + slug)
- `resources/views/admin/pages/edit.blade.php` (word count + slug)
- `resources/views/admin/settings/ai-agent.blade.php` (expand sections)
- `app/Http/Controllers/Admin/AiSiteBuilderAdminController.php` (PRG)

**Resolved** ✅
- Better editor feedback
- Form re-posting prevented
- Flexible timeout tuning

---

# Architecture & Key Design Decisions

## Module System
- Service registration via `App\Providers\CmsServiceProvider`
- `ModuleManager` discovers `modules/{name}/module.json`
- Modules register safe providers at boot

## Rendering Pipeline
- `App\Support\Cms` handles `@cmsContent()` directive
- Shortcodes: `[form slug="..."]`, `[icon kind="..." value="..." size="..." colour="..."]`
- HTML rendering controlled by `config('cms.allow_raw_html')`

## Security
- Shortcodes sanitised
- HTML escaping toggleable (default on)
- Preview routes protected with `X-Robots-Tag: noindex`
- Admin gate enforces `can:access-admin` on all backend routes

## Database
- Soft-delete (trash) pattern throughout (Pages, Forms, Users, etc.)
- Settings table for key/value + encrypted secrets
- Encrypted API keys via `Setting::setSecret/getSecret`

## AI Integration
- Provider pattern: OpenAI, Gemini, Fallback (NullLlmClient)
- Vision clients: separate interface for screenshot-based redesign
- Screenshot capture: PHP-only (headless Chrome/Edge)

## Testing
- PHPUnit with SQLite in-memory database
- Feature tests cover bulk operations + safety rules
- Predictable admin credentials for test environment

---

# Setup & Operations

## Initial Setup
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

## Admin Access
- **Email**: `lourens@2ko.co.za`
- **Password**: `L0ur3nsn3l2630`

## Configuration (key)
- `config/cms.php`: admin path, modules path, allow_raw_html
- `.env` overrides:
  - `AI_PROVIDER` (openai, gemini, etc.)
  - `OPENAI_API_KEY`, `OPENAI_MODEL`, `OPENAI_TIMEOUT`
  - `GEMINI_API_KEY`
  - `AI_SCREENSHOT_BIN` (Chrome/Edge path)

## Common Commands
```bash
php artisan migrate                 # Run migrations
php artisan db:seed               # Seed database
php artisan test                  # Run tests
npm run build                     # Build frontend assets
php artisan optimize:clear        # Clear caches
```

---

# Known Limitations & Future Work

## Not Implemented
- Drag-drop page builder (blueprint-based generation exists)
- Advanced role/permission system (admin/member binary for now)
- XML sitemap (SEO fields exist)
- Plugin marketplace (module architecture ready)

## Known Issues
- Icon rendering in forms sometimes depends on CSS load timing
- "Where used" media detection not comprehensive
- No true "template builder" UI (themes are code-based)

## Recommended Next Steps
1. Advanced user roles (Editor, Author, Contributor)
2. Media folder organization
3. Form field validation rules UI
4. Custom code snippets (CSS/JS injection)
5. Activity logging (audit trail)

---

**Document History**
- 2026-02-20: Initial ChatGPT change log created
- 2026-03-02: Merged with base project history; added AI integration, bulk admin actions, and fixes

