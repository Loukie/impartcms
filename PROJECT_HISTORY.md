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
- 2026-03-06 (Part 1): Site cloning feature implementation with AI-powered analysis and design extraction
- 2026-03-06 (Part 2): Media import integration, enhanced image extraction, soft delete system for images

---

# Part 4: Site Cloning & Enhanced AI Features

## 2026-03-06 – Site Cloning Feature Implementation

**What was done**
- Complete site cloning workflow (analyze → design system → blueprint → build)
- Implemented `SiteCloneAnalyzer` for fetching and parsing external websites
  - HTTP client with retry logic, SSL bypass for dev, proper User-Agent headers
  - HTML parsing via Symfony DomCrawler
  - Navigation extraction, page discovery, content sampling
  - Enhanced color detection (inline styles + CSS <style> tags)
  - Font detection from Google Fonts links
- Added `DesignSystemGenerator` for extracting unified design tokens
  - Analyzes site structure and generates cohesive color palette
  - Determines layout patterns, nav style, typography
  - AI-powered design system unification
- Enhanced `AiSiteBlueprintGenerator` with `generateForClone()` method
  - Clone-specific prompting with design system context
  - Visual richness guidance (hero sections, cards, testimonials)
  - Auto-fix for missing `is_homepage` field
- Improved `AiPageGenerator` with rich section templates
  - Hero sections with backgrounds and CTAs
  - Service/feature card grids with icons
  - Testimonial blocks with author attribution
  - Alternating image+text sections
  - Stats displays and full-width CTAs
- Enhanced `AiSiteBuilder` with canonical navigation and global CSS
  - Shared navigation extracted once and injected into all pages
  - Global CSS styling with design system variables
  - Professional polish (shadows, hover effects, spacing)
- Created `AiSiteCloneAdminController`
  - POST `/admin/site-clone/analyze` - fetch and analyze site
  - POST `/admin/site-clone/build` - build cloned pages
  - GET `/admin/site-clone/health` - LLM health check
  - GET `/admin/site-clone/debug-llm` - debug endpoint
- Built Vue 2 frontend UI (`ai-clone-site.blade.php`)
  - Step-by-step clone workflow
  - Pre-flight LLM health check
  - Analysis review (site title, nav, colors, pages found)
  - Design system preview
  - Build options (draft/publish, set homepage)
- Fixed OpenAI model configuration issues
  - Corrected default model from non-existent `gpt-5.x` to `gpt-4o`
  - Added model normalization function
  - Updated AI agent settings UI to show only valid models
- Fixed HTTP client compatibility issue (`followRedirects()` method doesn't exist)
- Created debug/test endpoints
  - `/test/llm-config` - verify LLM configuration
  - `/test/internet-access` - test outbound connectivity
  - `/test/site-clone-fetch/{url}` - test URL fetching
- Enhanced error handling with detailed logging throughout pipeline

**Why**
- Users need ability to clone existing sites with AI-powered improvements (like same.new)
- Design system extraction ensures visual consistency across cloned pages
- Rich templates create professional, modern-looking cloned sites
- Debug endpoints help troubleshoot configuration and connectivity issues

**Files created**
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php`
- `app/Support/Ai/SiteCloneAnalyzer.php`
- `app/Support/Ai/DesignSystemGenerator.php`
- `app/Support/Ai/AnthropicClient.php` (prepared for future use)
- `app/Support/Ai/LinkRewriter.php` (URL normalization)
- `resources/views/admin/pages/ai-clone-site.blade.php`

**Files modified**
- `app/Support/Ai/AiSiteBlueprintGenerator.php` - added clone-specific generation
- `app/Support/Ai/AiPageGenerator.php` - rich section template guidance
- `app/Support/Ai/AiSiteBuilder.php` - canonical nav + global CSS injection
- `app/Support/Ai/OpenAiResponsesClient.php` - model validation fixes
- `app/Providers/AppServiceProvider.php` - model normalization, default to gpt-4o
- `app/Http/Controllers/Admin/AiAgentSettingsController.php` - model options updated
- `routes/web.php` - clone routes, debug endpoints
- `.env.example` - corrected default OpenAI model

**Resolved** ✅
- Complete site cloning workflow from URL to published pages
- External website fetching with proper headers and SSL handling
- Color extraction from inline styles and CSS
- Design system unification via AI
- Canonical navigation consistency across all cloned pages
- Rich, professional page layouts with visual polish
- Model configuration validation and normalization
- LLM health checking before clone operations
- Auto-fix for missing blueprint fields

**Partially resolved** ⚠️
- Color detection works but may miss colors in external CSS files (only scans inline + <style> tags)
- No CSS stylesheet parsing (future enhancement)

**Not resolved** ❌
- External CSS file parsing for comprehensive color extraction
- Image downloading/hosting (cloned sites link to original images)
- JavaScript functionality cloning (only HTML/CSS)

**Known issues**
- Color detection limited to inline styles and embedded CSS
- Requires internet access from server to clone external sites
- Some sites block automated access (403 Forbidden)
- Cloned sites may need manual color refinement

**Technical notes**
- HTTP client uses `withoutVerifying()` for SSL bypass in local dev
- Laravel HTTP client doesn't have `followRedirects()` method (redirects handled automatically)
- Vue 2.6.14 loaded from CDN for frontend interactivity
- Retry logic: 2 retries with 500ms delay
- Max pages per clone: 3-15 (configurable)
- Color extraction returns up to 8 colors, filters out near-white/near-black
- Blueprint validation auto-adds missing `is_homepage` field (defaults first page to true)

---
## 2026-03-06 Part 2 – Media Integration, Image Enhancement & Soft Delete System

**What was done**
- Created `MediaImporter` service for downloading external media
  - Downloads images and videos from external URLs
  - Stores to `storage/app/public/media/YYYY/MM/` with UUID filenames
  - Supports comprehensive formats: jpg, jpeg, png, gif, webp, svg, ico, avif, bmp, tiff, mp4, webm, ogg, ogv, mov, avi, wmv, flv, mkv, m4v, 3gp
  - Extracts image dimensions via `getimagesize()`, MIME types for all files
  - HTTP download with timeout(15), SSL bypass, proper error handling
  - Creates MediaFile database records with metadata (size, dimensions, MIME type, caption)
  - User attribution via `created_by` field
  - Full logging of import successes and failures
- Enhanced `SiteCloneAnalyzer` image extraction
  - Hero images: 3 → 10 (includes slider images)
  - Content images: 10 → 30 (scans all sections, not just main/article)
  - Better XPath selectors for broader coverage
  - Deduplicated URLs to avoid duplicates in result sets
- Enhanced `AiSiteBlueprintGenerator` prompting
  - Added `📸 AVAILABLE MEDIA ASSETS` section in clone prompt
  - Instructions to use logo URL in all page headers
  - Instructions to reference specific image URLs in page briefs
  - Added `🎨 ICONS` section documenting FontAwesome/Lucide shortcodes
  - Available FontAwesome icons listed: fa-check, fa-users, fa-shield, fa-star, fa-heart, fa-phone, fa-envelope, fa-map-marker, fa-clock, fa-building, fa-cog, fa-chart-line, fa-briefcase, fa-lightbulb, fa-trophy, fa-comments, fa-thumbs-up, fa-rocket
- Enhanced `AiPageGenerator` with icon shortcode documentation
  - Added icon examples to brief context
  - FontAwesome: `[icon kind="fa" value="fa-solid fa-house" size="24" colour="#..."]`
  - Lucide: `[icon kind="lucide" value="home" size="24" colour="#..."]`
  - Instructs AI to use shortcodes instead of downloading icon images
- Integrated `MediaImporter` into build process (`AiSiteCloneAdminController`)
  - Extracts all image/video URLs from analysis
  - Downloads via MediaImporter during build
  - Creates external URL → internal storage URL mapping
  - Passes mapping to AiSiteBuilder
  - Skips downloading actual icon images (uses shortcodes instead)
  - Full logging of media import process
- Enhanced `AiSiteBuilder` with URL replacement
  - Added `replaceMediaUrls()` method
  - Replaces external URLs with internal storage URLs in page briefs before AI generation
  - Passes media_mapping via options array
- Updated frontend (`ai-clone-site.blade.php`)
  - Modified buildSite() to send analysis data to build endpoint
  - Analysis includes downloaded media for URL mapping
- Added soft delete support to `MediaFile` model
  - Uses Laravel's SoftDeletes trait
  - Adds `deleted_at` column via migration
  - Files remain on disk when "deleted"
  - Can be restored or force-deleted later
- Enhanced media deletion workflow
  - `MediaAdminController::bulk()` now uses soft delete
  - `MediaAdminController::destroy()` changed to soft delete (files stay on disk)
  - Added `MediaAdminController::trash()` - view all trashed media
  - Added `MediaAdminController::restore()` - restore from trash
  - Added `MediaAdminController::forceDelete()` - permanently remove from disk
- Created media trash UI
  - New view: `resources/views/admin/media/trash.blade.php`
  - Shows deleted_at timestamp
  - Restore or permanently delete buttons
  - Displays file info (size, dimensions, original name)
  - Paginated trash view
- Enhanced media admin index
  - Added "Trash" link to media type tabs
  - Easy access to trash from main media page
- Added routes for media trash system
  - GET `/admin/media/trash` - view trash
  - POST `/admin/media/trash/{id}/restore` - restore file
  - DELETE `/admin/media/trash/{id}/force` - permanent delete
- Enhanced `AiSiteBuilder` with improved error logging
  - Warns when pages generate very short content (< 50 chars)
  - Logs brief and HTML length for debugging
  - Better error messages with page title context
  - Adds warnings to response for user visibility
- Improved blueprint generation prompting
  - Added explicit brief requirements for all pages
  - 3-5+ sentence briefs mandatory
  - Examples of good vs bad briefs
  - Emphasis on avoiding vague/short briefs like "Contact page"
  - Special guidance for Privacy/Terms pages with structural requirements

**Why**
- Clone feature needs to pull images/videos from source sites and store locally
- FontAwesome integration exists but wasn't being used by AI
- Bulk delete should be reversible (soft delete pattern)
- Media trash system follows WordPress conventions
- Better error logging helps debug why some pages generate empty content

**Files created**
- `app/Support/MediaImporter.php` - service for downloading media
- `database/migrations/2026_03_06_120000_add_deleted_at_to_media_files_table.php` - soft delete migration
- `resources/views/admin/media/trash.blade.php` - trash view UI

**Files modified**
- `app/Models/MediaFile.php` - added SoftDeletes, isVideo() method, is_video attribute
- `app/Http/Controllers/Admin/MediaAdminController.php` - soft delete in bulk/destroy, added trash/restore/forceDelete methods
- `app/Support/Ai/SiteCloneAnalyzer.php` - increased image extract limits (3→10 hero, 10→30 content)
- `app/Support/Ai/AiSiteBlueprintGenerator.php` - added media asset docs, icon shortcode examples, stronger brief requirements
- `app/Support/Ai/AiPageGenerator.php` - icon shortcode context added to briefs
- `app/Support/Ai/AiSiteBuilder.php` - URL replacement, improved error logging, warnings for short content
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php` - media download integration, mapping creation
- `resources/views/admin/pages/ai-clone-site.blade.php` - send analysis to build endpoint
- `resources/views/admin/media/index.blade.php` - Trash tab added
- `routes/web.php` - media trash routes added

**Resolved** ✅
- External images/videos downloaded and stored locally during clone
- Internal storage URLs used instead of external links in generated HTML
- FontAwesome icon shortcodes documented and targeted in AI prompts
- Media files can be soft-deleted and restored (trash system)
- Better visibility into why pages generate empty content
- More comprehensive image extraction from cloned sites
- Better brief guidance for AI-powered content generation

**Partially resolved** ⚠️
- Icon extraction still in place (skipped during download, but detected during analysis)
- Not all page types may get thorough briefs yet

**Known issues**
- Last 2 pages in clones may generate blank/short content if briefs are vague
- OpenAI quota exceeded during testing (need API credits or switch to Claude)
- Media trash not auto-cleaned (requires manual force-delete for disk space)

**Technical notes**
- MediaImporter downloads with timeout(15) and withoutVerifying()
- Video dimensions not extracted (getimagesize fails for videos)
- Icon images skipped in media import but FontAwesome shortcodes used instead
- Soft delete uses `deleted_at` timestamp instead of permanent deletion
- Media files kept on disk for recovery/restoration
- `replaceMediaUrls()` simple string replacement (works for most URL patterns)
- Enhanced error logging helps identify brief quality issues

**Next steps to improve clone quality**
1. Increase brief detail further - all pages need 4-6 sentence briefs
2. Add image URL requirement verification in prompts
3. Test with more diverse websites (different industries/designs)
4. Add CSS stylesheet parsing for wider color detection
5. Implement automatic brief validation before page generation
6. Add retry logic for failed page generations

---

## 2026-03-09 - Clone Image Auto-Fallback (Broken/Missing Image Protection)

**What was done**
- Added automatic image fallback handling in `AiSiteBuilder` so cloned pages do not show broken image icons.
- Any `<img>` with a missing/empty `src` is now assigned an immediate generated SVG placeholder.
- Every generated `<img>` now gets an `onerror` fallback that swaps to a generated placeholder if the original image fails at runtime.
- Placeholder image is generated from clone context (design system colors + brand/domain label) so fallback visuals match the site theme.
- Applied fallback handling after media URL replacement and before final page body save.

**Why**
- Clone targets can have dead links, blocked hotlinks, or missing assets.
- Editors should never see broken image icons or blank image areas in generated pages.

**Files modified**
- `app/Support/Ai/AiSiteBuilder.php`

**Resolved** ✅
- Missing image sources now auto-fill with themed placeholders.
- Broken image URLs now auto-recover client-side using fallback placeholders.
- Clone output is visually resilient even when source media is unavailable.

**Note**
- This protects against missing/broken loads. It does not classify image content quality (for example, intentionally dark or low-quality source images).

### 2026-03-09 Update - Real Stored Fallback Image (Media Library)

**What was done**
- Added `FallbackImageGenerator` to create a real fallback SVG file in `storage/app/public/media/YYYY/MM/`.
- Automatically creates a `MediaFile` record so the fallback appears in Media Library.
- Clone build now generates one fallback media asset per run and passes its URL into `AiSiteBuilder`.
- `AiSiteBuilder` now prefers this stored fallback URL for missing/broken `<img>` tags (with data-URI as last-resort backup).

**Why**
- You requested fallback images to be real media assets, not inline data URIs.
- Real assets are reusable, visible in admin Media Library, and easier to audit/manage.

**Files modified**
- `app/Support/Ai/FallbackImageGenerator.php` (new)
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php`
- `app/Support/Ai/AiSiteBuilder.php`

### 2026-03-09 Update - AI-Generated Fallback Images (Preferred)

**What was done**
- Added provider-level AI image generation abstraction:
  - `AiImageClientInterface`
  - `OpenAiImageClient`
  - `NullAiImageClient`
- Bound image generation in `AppServiceProvider` (OpenAI-backed for now).
- Updated `FallbackImageGenerator` to attempt AI image generation first for fallback assets.
- If AI image generation succeeds, stores real AI image (PNG/JPG/WebP/SVG) in Media Library.
- If AI image generation fails, automatically falls back to generated SVG so clone build remains resilient.

**Why**
- Requirement: when source image is missing/broken, fallback should be a real AI-generated image instead of a plain static placeholder.

**Files modified**
- `app/Support/Ai/AiImageClientInterface.php` (new)
- `app/Support/Ai/OpenAiImageClient.php` (new)
- `app/Support/Ai/NullAiImageClient.php` (new)
- `app/Providers/AppServiceProvider.php`
- `app/Support/Ai/FallbackImageGenerator.php`
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php`

### 2026-03-09 Update - Per-Image Media Normalization (Always Local Media URLs)

**What was done**
- Added post-build image normalization in clone flow to process every `<img>` tag in generated pages.
- For each image source:
  - Try importing original source into Media Library.
  - If import fails or source is missing, generate AI image and save to Media Library.
  - Rewrite `<img src>` to the resulting local media URL.
- Local media URLs already under `/storage/media/...` are preserved.

**Why**
- Requirement: all images used by cloned pages should be stored in Media Library and linked locally.
- Requirement: if original image cannot be found/retrieved, always generate an AI image.

**Files modified**
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php`

### 2026-03-09 Update - Free Temporary Image Fallback (Last Resort)

**What was done**
- Extended `FallbackImageGenerator` fallback chain to include free temporary image providers.
- New order:
  1. Import original source image to Media
  2. AI generate replacement image to Media
  3. Download free temporary image to Media
  4. Final internal SVG fallback
- Free image fallback is downloaded and saved as a normal `MediaFile` record, then linked locally.

**Why**
- Requirement: if original + AI both fail, system should still use a usable temporary image instead of broken/black placeholders.

**Files modified**
- `app/Support/Ai/FallbackImageGenerator.php`

### 2026-03-09 Update - Added GPT-5.4 Thinking Model Option

**What was done**
- Added `gpt-5.4-thinking` to OpenAI model options in AI Agent settings UI.
- Updated OpenAI model normalization in settings save flow to accept aliases:
  - `gpt-5.4`
  - `gpt-5.4 thinking`
  - `gpt54-thinking`
- Updated runtime provider normalization to keep `gpt-5.4-thinking` valid when binding the OpenAI LLM client.

**Files modified**
- `app/Http/Controllers/Admin/AiAgentSettingsController.php`
- `app/Providers/AppServiceProvider.php`

---

### 2026-03-09 Update - CSS Background Image Support + Creative Context-Aware AI Image Generation

**What was done**
- Enhanced image normalization to process **inline CSS background images** in addition to `<img>` tags.
  - Now parses `style="background-image: url(...)"` and `style="background: url(...)"` attributes.
  - Applies same fallback chain: original import → AI generation → free temporary → SVG.
- Completely rewrote AI image prompt generation to be **creative and context-aware** instead of generic placeholders:
  - Analyzes page title, body content, and original image URL to understand context.
  - Detects image type: hero, team, contact, product, feature, location.
  - Detects industry from keywords: restaurant, tech, legal, healthcare, real estate, fashion, finance, creative agency, construction, education, fitness, automotive, travel, smart home/automated blinds, etc.
  - Generates **specific, photorealistic prompts** tailored to detected context.
  - Example: "Contact Us" page → generates "modern business exterior with glass facades and professional atmosphere" instead of generic placeholder.
  - Example: "Find Us Here" page → generates "welcoming office entrance with architectural details" instead of "Map Placeholder".
  - Explicitly instructs: **NO placeholder text**, **NO watermarks**, **NO "image unavailable" graphics**, **photorealistic quality**.
- Extracted `resolveImageSource()` helper method to centralize image resolution logic.

**Why**
- User reported broken background images behind sections like "Get in Touch".
- User requested: **"there should never be placeholder like this"** – wants creative, real images, not generic placeholder graphics.
- Clone results must look professional with context-appropriate imagery, not generic fallbacks.

**How it works**
1. Image normalization now detects both `<img>` tags and inline `style` attributes with CSS `url()` functions.
2. For each image URL:
   - Try importing original from source site.
   - On failure, generate AI image using **context-aware prompt** based on page content and detected industry.
   - Prompt generator analyzes text for keywords like "contact", "team", "product", "hero" and creates specific imagery.
   - Industry detection identifies business type (restaurant, tech, legal, etc.) and tailors imagery accordingly.
3. Generated image is saved to Media Library and URL is rewritten in HTML/CSS.

**Files modified**
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php`
  - Added CSS `background-image` and `background` processing in `materializeCloneImagesToMedia()`.
  - Extracted `resolveImageSource()` helper method.
- `app/Support/Ai/FallbackImageGenerator.php`
  - Completely rewrote `buildAiImagePrompt()` to be context-aware and creative.
  - Added `extractImageContext()` method to detect image type from page content.
  - Added `detectIndustry()` method to identify business type and appropriate imagery.
  - Now accepts `page_body` and `original_src` in context for better analysis.

**Example transformations**
- "Contact Us" / "Find Us Here" → Modern office exterior or welcoming entrance
- "Get in Touch" → Professional business atmosphere with architectural details
- "Our Team" → Collaborative workspace with natural light (no people)
- "Explore Our Styles" → Elegant fashion displays or product showcase
- "About Us" → Modern office environment with contemporary design
- Automated blinds site → Smart home imagery with modern window treatments

**Impact**
- No more generic placeholder images in cloned sites.
- Every generated image is contextually appropriate and looks professional.
- Background images in CSS are now properly handled alongside `<img>` tags.

---

## 2026-03-10 - Clone Quality Controls + Multi-Client Defaults + Navigation Variation

**What was done**
- Added an AI image fallback toggle in Admin settings (`AI Agent`):
  - `ON` = allow AI image generation during clone fallback.
  - `OFF` = never use AI image generation in clone fallback pipeline.
- Updated clone fallback pipeline order for missing/broken images:
  1. Import original source image.
  2. Try contextual replacement from Media Library (subject/business-aware scoring).
  3. Use generated non-AI fallback asset when no match is found.
- Added clone design defaults in Admin settings for reusable quality control:
  - `Design mode`: `safe`, `premium`, `strict_reference`.
  - Optional brand defaults: `primary/secondary/accent` and `global font`.
  - Added `Enforce brand tokens globally` toggle for agency/multi-client behavior.
- Implemented multi-client behavior:
  - When enforcement is OFF (recommended), brand colors/font are treated as soft hints.
  - When enforcement is ON, colors/font are hard-applied to clone design generation.
- Replaced hardcoded canonical nav style with variant-aware nav generation:
  - Supports style variants (`modern`, `centered`, `split`, `minimal`) based on design cues.
  - Added active link state, brand/CTA treatment, improved responsive behavior.
  - Enforced navigation baseline behavior:
    - Homepage top: transparent overlay.
    - Homepage on scroll: solid/light.
    - Inner pages: solid/light.

**Why**
- Reduce AI image quota usage and avoid unnecessary generation costs.
- Improve clone quality consistency without requiring long manual prompts every run.
- Keep system suitable for multi-client site building (no forced single-brand lock by default).
- Remove repetitive/generic header output that made clones look template-like.

**Files modified**
- `app/Http/Controllers/Admin/AiAgentSettingsController.php`
- `resources/views/admin/settings/ai-agent.blade.php`
- `app/Http/Controllers/Admin/AiSiteCloneAdminController.php`
- `app/Support/Ai/FallbackImageGenerator.php`
- `app/Support/Ai/AiPageGenerator.php`
- `app/Support/Ai/AiSiteBuilder.php`

**Resolved** ✅
- Clone fallback can be switched between AI-enabled and non-AI via UI.
- Missing image fallback now prefers relevant existing media before generating new assets.
- Clone prompts now include stronger premium design contract by default.
- Multi-client mode supported via non-enforced brand defaults.
- Navigation output no longer forced to one generic header style.