# PROJECT_HISTORY_WITH_CHATGPT

## ✅ ChatGPT Changes — 2026-03-02 (Africa/Johannesburg, SAST)

### What changed
- Added **Admin → AI Agent** settings to choose an AI provider (**OpenAI / Gemini / Disabled**), store API keys **encrypted** in the Settings table, set timeouts, and pick models via dropdown (Gemini list includes “free-tier available” options).
- Added **AI Page generation** (Admin → Pages → **AI Page**) that creates a page and injects **sanitised HTML** into `pages.body` (safe defaults: saves as **draft**).
- Added **AI Site Builder** (Admin → Pages → **AI Site Builder**) that:
  - generates a **JSON blueprint** (sitemap + per-page briefs + SEO),
  - then bulk-creates pages and injects HTML per page (slug uniqueness + draft-first safety).
- Added **Gemini support**:
  - **Text** generation via `GeminiGenerateContentClient` (blueprints + page HTML).
  - **Vision** redesign via `GeminiVisionClient` (screenshot-based redesign in Visual Audit).
- Added a global **Admin AI popup** (“AI ✨”) available across admin screens:
  - page search + page context auto-detect on edit screens,
  - tweak/rewrite modes,
  - saves as **draft by default**,
  - stores a **revision backup** before overwriting.
- Added **AI Visual Audit** (screenshots + redesign):
  - captures screenshots for a selected page (including drafts via signed preview URL) + a reference URL,
  - runs Gemini vision redesign,
  - saves redesigned HTML as **draft**.
  - Screenshot capture is **PHP-only** using headless **Chrome/Edge** (no Node dependency).
- Homepage safeguards:
  - homepage can only be set on **published** pages,
  - added **Unset Home** per page and **Clear Home** global action,
  - AI flows that save drafts **unset homepage** to avoid draft homepages.
- Added **bulk delete/trash** capability to admin list pages (Pages, Media library, Forms, Users, Header/Footer blocks, Custom snippets) with checkboxes, batch actions, new routes/controllers, and corresponding tests.

### Files added
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

### Files edited (key)
- `app/Providers/AppServiceProvider.php` (bind provider-selected LLM + Gemini-only vision client)
- `routes/web.php` (AI Agent, AI Page, Site Builder, AI popup endpoints, Visual Audit routes)
- `resources/views/layouts/admin.blade.php` (sidebar links + AI popup include)
- `resources/views/admin/pages/index.blade.php` (homepage toggle + Clear Home UI)
- `app/Http/Controllers/Admin/PageAdminController.php` (homepage set/unset/clear endpoints)
- `app/Http/Controllers/PageController.php` (signed preview route for screenshot capture)
- `app/Support/Ai/AiPageGenerator.php` and `app/Support/Ai/HtmlSanitiser.php` (sanitised HTML generation)

### Operational notes
- After applying these changes, run:
  - `php artisan migrate`
  - `php artisan optimize:clear`
- Screenshot capture requires Chrome/Edge. Optional `.env` override:
  - `AI_SCREENSHOT_BIN='C:\Program Files\Google\Chrome\Application\chrome.exe'`
  - (Use **single quotes** or forward slashes to avoid dotenv escape issues.)


## ✅ ChatGPT Changes — 2026-02-20 08:10:54 UTC

### What changed
- Notice bar fades away on scroll and reappears at the top (prevents overlap with navigation).
- Shortcodes now work in Global Header, Global Footer, and Notice Bar.
- Shortcode detection scans body + header/footer + notice so required front-end assets load automatically.

### Files edited
- app/Http/Controllers/PageController.php
- resources/views/themes/default/page.blade.php
- resources/views/themes/default/page-blank.blade.php

---

# PROJECT_HISTORY

## ✅ ChatGPT Changes — 2026-02-20 11:20:14 SAST

### What changed
- Added **notification bar styling controls**: **background colour** (hex) + **minimum height** (px).
- Notification bar now **auto-picks a readable text colour** based on the chosen background (dark bg → white text, light bg → dark text).
- Settings UI now includes a **colour picker + hex input** (kept in sync) and a **height field**.

### Files edited
- `app/Http/Controllers/Admin/SettingsController.php`
- `resources/views/admin/settings/edit.blade.php`
- `resources/views/themes/default/page.blade.php`
- `resources/views/themes/default/page-blank.blade.php`

---

> Source: `changelog.txt` supplied by the user (as-is).
> Note: Some dates/times may be approximate or incomplete in the original log; they are preserved below.

---

## 1️⃣ SEO Output Layer Activation
Files Modified

resources/views/themes/default/page.blade.php

What Was Changed

Implemented full SEO rendering inside <head>:

<title>

<meta name="description">

<link rel="canonical">

<meta name="robots">

OpenGraph tags (og:title, og:description, etc.)

Twitter tags (twitter:title, etc.)

Added safe fallbacks:

Title falls back to page title or app name

Description falls back to config default

Canonical falls back to url()->current()

Robots defaults to index,follow

Why

Previously, SEO fields were saved in the database but were not guaranteed to render into the HTML head. That means Google/social previews would not work reliably.

What This Resolved

SEO meta is now actually functional.

Social sharing previews work.

Canonical and robots always present.

Preview route remains protected by controller-level X-Robots-Tag.

What Is Not Yet Implemented

Global SEO defaults via Settings.

XML sitemap.

Robots.txt editor.

## 2️⃣ RankMath-Style Page Editor (2-Column Layout)
Files Modified

resources/views/admin/pages/create.blade.php

resources/views/admin/pages/edit.blade.php

What Was Changed

Converted editor into 2-column layout:

Left: content (title, slug, body)

Right: sidebar panels

Sidebar panels:

Status/actions (Save Draft / Publish / Preview)

SEO panel with tabs:

General

Social

Advanced

Added:

Live SERP preview

Simple SEO score

Basic checklist (JS-only)

Kept all form input names unchanged.

Why

You requested RankMath-like SEO behaviour and WordPress-style UX. The goal was to improve editorial clarity without touching backend logic.

What This Resolved

Cleaner separation between content and SEO.

Editors can see how search results will look.

No controller changes required.

What Is Not Yet Implemented

No focus keyword scoring logic.

No dynamic SEO templates like %sitename%.

## 3️⃣ Pages List + Trash UI Refinement
Files Modified

resources/views/admin/pages/index.blade.php

resources/views/admin/pages/trash.blade.php

What Was Changed

Cleaned table layout.

Preserved existing actions:

View Live

Preview Draft

Edit

Trash

Restore

Force Delete

Improved structure to align with new sidebar layout.

Why

Consistency with new admin layout and removal of redundant navigation.

What This Resolved

Cleaner and more CMS-like presentation.

No behavioural changes.

What Is Not Yet Implemented

No badge counters (e.g. number of trashed pages).

No bulk actions.

## 4️⃣ WordPress-Style Global Admin Sidebar
Files Created

resources/views/admin/partials/sidebar.blade.php

Files Modified

resources/views/layouts/app.blade.php

What Was Changed

Added global left sidebar rendered only when:

Route matches admin prefix

User passes access-admin gate

Sidebar includes:

Pages

Trash

Settings

Forms (placeholder)

Sidebar made sticky on larger screens.

Layout wrapped in responsive grid.

Why

Admin navigation should be centralized, not repeated per screen.

What This Resolved

Unified navigation system.

Eliminated need for inline page tabs.

What Is Not Yet Implemented

Role-based menu visibility beyond admin.

Sidebar badge counts.

## 5️⃣ Removed Duplicate Inline Navigation Strip
Files Modified

resources/views/admin/pages/index.blade.php

resources/views/admin/pages/create.blade.php

resources/views/admin/pages/edit.blade.php

resources/views/admin/pages/trash.blade.php

What Was Removed

This entire block was deleted:

<nav class="flex flex-wrap items-center gap-2">
   ...
</nav>

Why

Once the global sidebar existed, this inline nav caused duplicated navigation and clutter.

What This Resolved

Cleaner UI.

No double navigation.

Proper WordPress-like hierarchy.

## 6️⃣ Settings Module v1 (Branding)
Files Created

database/migrations/*_create_settings_table.php

app/Models/Setting.php

app/Http/Controllers/Admin/SettingsController.php

resources/views/admin/settings/edit.blade.php

Files Modified

routes/web.php

resources/views/layouts/app.blade.php

resources/views/admin/partials/sidebar.blade.php

What Was Implemented

settings table (key/value storage)

Cached Setting model

/admin/settings page:

Site Name input

Logo upload/remove

Updated layout <title> to use stored site name.

Sidebar displays logo + site name.

Why

You requested admin control over app/site name and branding.

What This Resolved

Site name is no longer hardcoded.

Logo is configurable via UI.

Foundation for future global settings.

What Is Not Yet Implemented

Favicon management.

Global SEO defaults.

Analytics snippet injection.

Top navigation branding update (if not yet aligned).

## 7️⃣ Layout Grid Adjustment (Sidebar Stacking Fix)
File Modified

resources/views/layouts/app.blade.php

What Was Changed

Grid breakpoint adjusted to md:grid-cols-12.

Ensured sidebar column and main content column widths defined.

Advised Tailwind rebuild via npm run build.

Why

Sidebar was stacking instead of appearing side-by-side.

What This Resolved

Proper sidebar layout on desktop.

Responsive behaviour consistent.

What Is Not Yet Implemented

Mobile-optimized sidebar toggle.

Collapsible menu.

✅ Current System State Summary

Working:

Pages CRUD


---
---

## ImpartCMS – Development Change Log
## 2026-02-10 – Initial Laravel + Breeze Setup
Files / Areas

Fresh Laravel 12 install

Breeze auth scaffolding

.env configuration

Database migrations

What was done

Installed Laravel via Composer

Installed Laravel Breeze (Blade)

Configured MySQL DB

Ran base migrations

Configured Git + private repo

Why

Foundation for custom CMS.

Resolved

Working authentication

Login/register/dashboard

Stable DB structure

Outstanding

Roles beyond admin not yet implemented

## 2026-02-11 – Pages System v1 (Core CMS Feature)
Files Changed / Created

app/Models/Page.php

app/Http/Controllers/Admin/PageAdminController.php

app/Http/Controllers/PageController.php

routes/web.php

resources/views/admin/pages/*

Migration for pages table

Migration for seo_meta table

What was done

Full CRUD pages system

Slug-based routing

Catch-all public route

Homepage flag (is_homepage)

Draft / Published status

published_at

SEO meta relation

Why

Pages are core CMS feature.

Resolved

Create/edit/delete pages

Public page rendering

Draft pages not public

SEO saved per page

Outstanding

SEO output not yet wired into <head>

## 2026-02-11 – Admin-only Draft Preview
Files

PageController.php

routes/web.php

What was done

Added _preview/pages/{page}

Route model binding with withTrashed()

Added X-Robots-Tag: noindex, nofollow

Why

Admins must preview drafts safely.

Resolved

Draft preview works

Trashed preview works

No accidental indexing

Outstanding

None

## 2026-02-11 – Trash System (Soft Deletes)
Files

Migration adding deleted_at

Page model uses SoftDeletes

PageAdminController

admin/pages/trash.blade.php

Route model binding for trashed pages

What was done

Soft delete instead of hard delete

Restore functionality

Force delete functionality

Trash list view

Why

WordPress-style safety.

Resolved

No accidental permanent delete

Restore works

No 404 on restore

Outstanding

Trash removed from sidebar later per UI decision

## 2026-02-12 – Fix: Page Deleting on Update
Files

resources/views/admin/pages/edit.blade.php

What was done

Removed nested forms

Separated delete form from update form

Adjusted submit buttons logic

Why

Browser was submitting wrong form → deleting pages unintentionally.

Resolved

Update no longer deletes page

Publish/Draft safe

Form behavior stable

Outstanding

None

## 2026-02-12 – Improve Draft / Publish UX
Files

edit.blade.php

index.blade.php

What was done

“Publish” only for new pages

Existing draft shows “Go Live”

Existing published shows “Update”

Added Created / Updated timestamps

Why

Avoid confusion between create vs update.

Resolved

Clear state logic

Better UX clarity

Outstanding

None

## 2026-02-12 – Admin Sidebar Always Visible
Files

resources/views/layouts/admin.blade.php

resources/views/components/layouts/admin.blade.php

resources/views/components/admin-layout.blade.php

Replaced <x-app-layout> in admin views

What was done

Built custom admin layout

Sidebar always visible

Unified admin layout across pages

Why

Dashboard had no sidebar previously.

Resolved

Sidebar consistent across backend

Admin navigation unified

Critical Fix

Forwarded component layout to actual layout to stop “no changes showing” issue.

Outstanding

None

## 2026-02-12 – Sidebar Structural Changes
Files

layouts/admin.blade.php

What was done

Added Settings back to sidebar

Removed Trash from sidebar

Moved View site under Dashboard in sidebar

Removed “View site” from top bar

Kept “View Live” on page rows unchanged

Why

Clear separation between:

System navigation

Page actions

Public site access

Resolved

Sidebar now matches desired admin UX

Cleaner top bar

Outstanding

Ensure settings route name matches sidebar

## 2026-02-12 – Component Layout Resolution Fix
Files

components/layouts/admin.blade.php

What was done

Forced component to include layouts.admin

Prevented Blade resolving wrong layout

Why

Edits to layout weren’t reflecting due to component precedence.

Resolved

UI changes now reliable

One true layout source

Current System Status
Fully Working

Auth system

Admin gate

Pages CRUD

Draft workflow

Publish workflow

Soft delete (Trash)

Restore

Force delete

Draft preview

Admin-only preview

Sidebar layout stable

Settings visible in sidebar

View site placement correct

Created / updated timestamps visible

Not Yet Implemented
SEO Output Layer

Meta tags not yet printed in <head>

Needs:

Title logic

Meta description

Canonical

OG

Twitter

(Data layer exists — output layer pending)

Future Planned

Forms module

Plugin/module architecture

Role system (editor/author)

Media manager

Architectural Notes

Catch-all route must remain last.

SoftDeletes required for Trash.

Preview route must always use withTrashed().

Admin layout must route through component forwarder.

---
---

# ImpartCMS – Dev Change Log (2026-02-11 → 2026-02-12)

> Source of truth: the zip bundles produced during this workstream.
> Important: Most bundles are **cumulative** (later bundles include earlier changes + new additions).
> When in doubt, apply the **latest** bundle in the chain you’re working on.

---

## 2026-02-11 10:28 — Admin layout, sidebar behaviour, settings plumbing (base)
Bundle: `impartcms_updated.zip`

### What + Why
- Standardised the admin layout so sidebar/topbar are consistent across admin screens.
- Ensured the “component layout forwarding” works (avoids the “no changes showing” issue when editing `layouts/admin.blade.php`).
- Admin settings scaffolding aligned with the sidebar.

### Key files touched (high impact)
- `resources/views/layouts/admin.blade.php`
- `resources/views/components/layouts/admin.blade.php` (forwarding fix)
- `resources/views/components/admin-layout.blade.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `app/Http/Controllers/Admin/PageAdminController.php`
- `routes/web.php`
- (Also includes Breeze auth controller files due to layout integration)

### Resolved ✅
- Sidebar shows consistently on admin pages.
- Layout forwarding prevents “edited file but UI didn’t update”.

### Not resolved ❌
- None from this bundle alone.

---

## 2026-02-11 10:58 — Homepage selection restored (WP-style)
Bundle: `impartcms_updated_homepage.zip`

### What + Why
- Restored “Set as homepage” functionality (previously existed in Actions column).
- Added server-side support for selecting the homepage.

### Key files touched
- `app/Http/Controllers/Admin/PageAdminController.php`
- `app/Models/Page.php` (homepage flag logic)
- `resources/views/admin/pages/index.blade.php` (homepage action restored)
- `resources/views/admin/settings/edit.blade.php` (homepage/landing selection UI wiring)
- `routes/web.php`

### Resolved ✅
- Can set a specific page as homepage again.

### Not resolved ❌
- None noted at this stage.

---

## 2026-02-11 11:23 — Prevent deleting homepage
Bundle: `impartcms_updated_homepage_nodelete.zip`

### What + Why
- Added a guard: homepage pages cannot be soft-deleted (or deletion blocked while flagged).
- Prevents accidental “site breaks” like WP does.

### Key files touched
- `app/Http/Controllers/Admin/PageAdminController.php`
- `resources/views/admin/pages/index.blade.php` (UX messaging / disabled action)
- `routes/web.php`

### Resolved ✅
- Homepage can’t be deleted while set as homepage.

### Not resolved ❌
- None.

---

## 2026-02-11 11:50 — Users admin (list + role visibility)
Bundle: `impartcms_updated_users.zip`

### What + Why
- Added Users management section like a normal CMS:
  - List users
  - Show who is admin
  - Toggle admin/member

### Key files touched
- `app/Http/Controllers/Admin/UserAdminController.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/layouts/admin.blade.php` (sidebar link)
- `routes/web.php`

### Resolved ✅
- Users list + admin visibility + basic role toggle.

### Not resolved ❌
- Creating users not yet included (next bundle adds it).

---

## 2026-02-11 12:00 — Users: Create user (backend)
Bundle: `impartcms_updated_users_create.zip`

### What + Why
- Added “New User” flow inside the CMS backend.

### Key files touched
- `app/Http/Controllers/Admin/UserAdminController.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/index.blade.php`
- `routes/web.php`

### Resolved ✅
- Admins can create users in the backend.

### Not resolved ❌
- Password handling not yet ideal (next bundle improves).

---

## 2026-02-11 12:09 — Users: Password reset handling + random password generator
Bundle: `impartcms_updated_users_passwordgen.zip`

### What + Why
- Added a practical CMS approach:
  - Generate random password for newly created users OR
  - Trigger reset email flow for existing user

### Key files touched
- `app/Http/Controllers/Admin/UserAdminController.php`
- `resources/views/admin/users/create.blade.php`
- `routes/web.php`

### Resolved ✅
- Password generation + reset workflow exists.

### Not resolved ❌
- None critical reported here.

---

## 2026-02-11 12:45 — Media library (upload + organise + detail/edit)
Bundle: `impartcms_updated_media.zip`

### What + Why
- Introduced Media manager:
  - Upload images + PDFs
  - Organise into YYYY/MM folders
  - View details + update title/alt/caption
  - Show “Where used” (initial detection / placeholder logic)

### Key files touched
- `app/Http/Controllers/Admin/MediaAdminController.php`
- `app/Models/Media.php` (if present)
- `database/migrations/*media*` (only if included)
- `resources/views/admin/media/index.blade.php`
- `resources/views/admin/media/show.blade.php`
- `routes/web.php`
- `config/filesystems.php` / `config/cms.php` (where applicable)

### Resolved ✅
- Media module added + works with uploads & browsing.

### Not resolved / Known issue ⚠️
- Public URLs depend on `APP_URL` being correct.
  If `APP_URL=http://localhost` but you browse via `http://2kocms.test`, URLs look “wrong” (though files still exist).

---

## 2026-02-11 14:08 — Sidebar: Media link + Settings logo behaviour (don’t delete media)
Bundle: `impartcms_sidebar_media_logo_icons_fix.zip`

### What + Why
- Added Media to the sidebar.
- Branding rules:
  - If no logo → show text
  - If logo exists → logo-only by default
  - Optional toggle: logo + text
- “Remove logo” clears the setting ONLY (does not delete from media library)

### Key files touched
- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/settings/edit.blade.php`
- `app/Http/Controllers/Admin/SettingsController.php`

### Resolved ✅
- Media appears in sidebar.
- Removing logo doesn’t delete the media asset.

### Not resolved ❌
- None at this step.

---

## 2026-02-11 14:19 — Sidebar icons for menu items
Bundle: `impartcms_admin_sidebar_icons_patch.zip`

### What + Why
- Added icons to: Dashboard / View site / Pages / Media / Users / Settings

### Key files touched
- `resources/views/layouts/admin.blade.php`
- (Possibly) `resources/css/app.css` or a small admin CSS include (depending on implementation)

### Resolved ✅
- Sidebar icons appear.

### Not resolved ❌
- None.

---

## 2026-02-11 14:43 — Full-width admin layout (partial)
Bundle: `impartcms_admin_fullwidth_patch.zip`

### What + Why
- Removed the `max-width: 80rem` container constraint so admin screens feel “fuller”.

### Key files touched
- `resources/views/layouts/admin.blade.php`
- `resources/views/components/layouts/admin.blade.php` (if wrapper applied width)

### Resolved ✅
- Full-width applied to the pages covered by this patch.

### Not resolved ⚠️
- Not all admin pages yet (next patch makes it consistent everywhere).

---

## 2026-02-11 14:58 — Full-width across all admin pages
Bundle: `impartcms_admin_fullwidth_all.zip`

### What + Why
- Ensured the full-width container rule applies globally (existing + future admin pages using the shared layout).

### Key files touched
- `resources/views/layouts/admin.blade.php`
- `resources/views/components/layouts/admin.blade.php`
- Any admin page wrappers that were still using a max-width container

### Resolved ✅
- Full-width is now “default behaviour” for admin screens using the shared admin layout.
- New admin screens created in future will inherit full width automatically (as long as they use `<x-admin-layout>` / admin layout).

### Not resolved ❌
- None.

---

## 2026-02-12 07:19 — Search + filters (WP-style modern take)
Bundle: `impartcms_search_filters_wpstyle.zip`

### What + Why
- Added modern WordPress-like filtering/search UX for:
  - Pages
  - Media
  - Users
- Goal: faster scanning in real CMS usage.

### Key files touched
- `app/Http/Controllers/Admin/PageAdminController.php`
- `resources/views/admin/pages/index.blade.php`
- `app/Http/Controllers/Admin/MediaAdminController.php`
- `resources/views/admin/media/index.blade.php`
- `app/Http/Controllers/Admin/UserAdminController.php`
- `resources/views/admin/users/index.blade.php`

### Resolved ✅
- Search + filters present on those screens.

### Not resolved ❌
- None reported here.

---

## 2026-02-12 09:54 — Media picker modal (attempt #1: also introduced fonts)
Bundle: `impartcms_media_picker_fonts_bundle.zip`

### What + Why
- Started WordPress-style “Select media” popup flow.
- Added support for font file uploads (later you decided to remove fonts again).
- Began favicon support direction.

### Key files touched
- `resources/views/admin/media/picker.blade.php`
- `resources/views/components/admin/media-picker.blade.php`
- `app/Http/Controllers/Admin/MediaAdminController.php`
- `routes/web.php`
- `resources/js/admin/*` (picker JS integration)
- `resources/css/app.css` (modal styles if any)

### Resolved ✅
- Picker UI started.

### Not resolved / Regressions ⚠️
- “Fonts” direction was later reversed (caused confusion in tabs/UX).
- Some routes/method wiring wasn’t stable yet.

---

## 2026-02-12 14:29 — Media picker modal (attempt #2: larger modal + toolbar alignment)
Bundle: `impartcms_media_picker_patch.zip`

### What + Why
- Made popup bigger.
- Intended alignment:
  - Left: Library / Upload / Cancel
  - Right: Folder dropdown + Search + Apply + Reset
- Added clearer README instructions for applying.

### Key files touched
- `resources/views/admin/media/picker.blade.php`
- `resources/views/components/admin/media-picker.blade.php`
- `app/Http/Controllers/Admin/MediaAdminController.php`
- `routes/web.php`
- `README_APPLY.md` (inside bundle)

### Resolved ✅
- Modal layout improvements.

### Not resolved ⚠️
- At one point: `Call to undefined method MediaAdminController::picker()` (controller mismatch vs route) surfaced depending on which version of controller/routes were live.

---

## 2026-02-12 17:21 — Media picker centring fix
Bundle: `impartcms_fixes_media_picker_centered.zip`

### What + Why
- Fixed modal centring/overlay behaviour so it sits centred and usable.

### Key files touched
- `resources/views/admin/media/picker.blade.php`
- `resources/views/components/admin/media-picker.blade.php`
- `resources/css/app.css` (or admin CSS area)

### Resolved ✅
- Modal centring improved.

### Not resolved ⚠️
- Icons strategy still not stable.

---

## 2026-02-12 21:53 — Icon shortcodes bundle (framework attempt)
Bundle: `impartcms_icons_shortcodes_bundle.zip`

### What + Why
- Introduced an approach for icon usage via shortcodes + preview.
- Included documentation scaffold.

### Key files touched (under bundle folder path)
- `impart_icons_shortcodes/resources/js/admin/icon-library.js`
- `impart_icons_shortcodes/resources/js/admin/icon-render.js`
- `impart_icons_shortcodes/resources/views/*`
- `impart_icons_shortcodes/docs/MANIFEST.md`

### Resolved ✅
- Documentation + initial icon approach introduced.

### Not resolved / Regressions ❌
- Bundle structure wasn’t “drop-in” at repo root (files nested under `impart_icons_shortcodes/`), so it could be confusing to apply.
- Later builds failed due to icon library import strategy (see next).

---

## 2026-02-12 22:54 — Vite build fix for Font Awesome metadata import
Bundle: `impartcms_fa_vite_fix_patch.zip`

### What + Why
- Vite/Rollup failed to resolve:
  - `@fortawesome/fontawesome-free/metadata/icons.json`
- Fix switched to a “no-metadata-import” approach so Vite doesn’t choke.

### Key files touched
- `resources/js/admin/icon-library.js`

### Resolved ✅
- Avoids the specific `icons.json` rollup resolution problem.

### Not resolved ❌
- `lucide` import/build error still occurs:
  - “Failed to resolve entry for package lucide…”

---

## 2026-02-12 23:17 — Media picker tabs clean-up (Icons/Fonts removed, simplified tabs)
Bundle: `impartcms_media_icons_tabs_fix.zip`

### What + Why
- Attempted to stabilise the picker by returning to simpler tabs:
  - All / Images / Docs
- Re-added/ensured `picker()` exists on the controller and route is valid.

### Key files touched
- `app/Http/Controllers/Admin/MediaAdminController.php` (ensures `picker()` exists)
- `resources/views/admin/media/picker.blade.php`
- `resources/views/components/admin/media-picker.blade.php`
- `routes/web.php`

### Resolved ✅
- The “undefined picker()” error should be resolved when these files are correctly applied.

### Not resolved ❌
- Icons (Font Awesome + Lucide) grid is not implemented in a stable way yet.
- Your requirement (“Top half FA, bottom half Lucide, selectable”) is not finalised.

---

# Summary: What’s resolved vs not

## ✅ Resolved / Stable
- Admin layout forwarding + consistent sidebar across admin screens
- Pages CRUD + Trash + Preview flow (from your base manifest)
- Homepage selection restored + guard against deleting homepage
- Users: list + roles + create + password handling (generator/reset)
- Media: upload + organise + browse + detail/edit (core library)
- Sidebar icons + Media link + branding rule (logo/text) + favicon field started
- Full-width admin across all admin pages using the shared layout
- WP-style search + filters on Pages/Media/Users

## ⚠️ Partially resolved / Needs refinement
- Media “in use” logic: currently blocks updates/deletes too aggressively in some cases
- Media picker modal UX: centring and sizing improved, but needs polish + consistency

## ❌ Not resolved / Current known problems
- Icon library inside picker:
  - Font Awesome + Lucide selectable grids
  - Stable build + stable runtime rendering
- Vite build errors when `lucide` is imported as a package on your setup
- Inconsistent “tabs” between Media page and Select Media modal depending on which patch set is active

---

# What I recommend as the next fix direction (tomorrow)
1) Pick one icon strategy that is build-safe:
   - Either: ship SVG icon sets without npm package imports (no Vite pain)
   - Or: use a known-good icon package configuration compatible with Vite 7 on Windows
2) Implement icon picker as a separate “virtual library” inside the modal (not mixed with Media DB records).
3) Only after that: add icon shortcodes + preview + size/colour controls.



---
---

## Timeline of work
2026-02-13 — Media + Icons foundation (UI + behaviour)

Goal: make Media + icon library usable and consistent (Media page + picker modal).

MANIFEST

Changes

Standardised Media tabs to match your request: Images / Icons / Docs and remove “Fonts”.

MANIFEST

Where: Media index + Media picker modal templates (Blade views)

Why: you had mismatch (Media page ≠ popup), and “Fonts” wasn’t needed

Resolved: ✅ tabs are aligned now

Fixed “FILE” tiles / broken thumbs

Where: app/Models/MediaFile.php

Why: views expected computed properties (like “is this an image?”, extension, etc.). Without accessors everything looked like a generic file.

Resolved: ✅ images show as images again

Fixed “full cover crop / funny looking thumbnails”

Where: Media grid Blade view

Why: object-cover crops; you wanted cleaner previews

Resolved: ✅ thumbnails display correctly

Admin layout crash after login (Undefined $slot)

Where: resources/views/layouts/admin.blade.php (layout wrapper)

Why: some pages were using Blade components (slot) while others used @extends (yield)

Resolved: ✅ layout now supports both, so login no longer bombs

2026-02-13 — Icon system upgrades (copy shortcode + select mode)

Goal: click icon → copy a working shortcode (not just class name), and support picker “Select”.

MANIFEST

Changes

Icon click now copies shortcode

Where: resources/js/admin/icon-library.js

Why: you want [icon kind="fa" value="..." size=".." colour=".."] (and you confirmed it works)

Resolved: ✅ click → copies shortcode (size/colour respected)

icon-library

icon-library

Added action toggle (Copy ↔ Select)

Where: resources/js/admin/icon-library.js + Icons UI partial (Blade)

Why: in the picker modal you sometimes want “Select”, on Media page you usually want “Copy”

Resolved: ✅ select mode posts payload back to opener; copy mode copies shortcode

icon-library

Colour picker + hex sync

Where: Icons UI partial (Blade) + icon-library.js

Why: stop typing hex manually, but keep it editable

Resolved: ✅ working (picker + text stay in sync)

icon-library

2026-02-13 → 2026-02-17 — Vite build fixes (Font Awesome list generation)

Goal: stop Rollup from failing on FA metadata imports and make builds reliable.

Problems hit

Missing ./fa-icon-list import

@fortawesome/fontawesome-free/metadata/icons.json not resolvable in your installed package (Rollup error)

Final solution implemented

Generate a local JSON icon list during build (stable, version-agnostic)

Files

scripts/generate-fa-icons.mjs (generator script)

resources/js/admin/fa-icons.json (generated output)

resources/js/admin/fa-icon-list.js (imports local JSON)

package.json scripts updated (build runs generator first)

Why

Avoid relying on FontAwesome internal metadata path (which differs by version / packaging)

Ensure Vite always has a local file to bundle

Resolved

✅ npm run build succeeds

✅ icons still render + searchable

2026-02-17 — Icon search bug fix (after the generator switch)

Problem: Icon search stopped working.

Root cause

Old filtering logic referenced it.search (which doesn’t exist in the generated list). You saw this in your file.

icon-library

Fix

Updated computeMatches() to search against safe fields (name/class/style) and not rely on it.search.

Files

resources/js/admin/icon-library.js

Resolved

✅ search works again (confirmed)

What’s resolved ✅

Media tabs consistent (Images / Icons / Docs)

MANIFEST

Admin login $slot error fixed

Thumbnails display properly

Font Awesome icons render

Icon click copies shortcode using size/colour

icon-library

Toggle Copy/Select works (picker vs page)

icon-library

Build pipeline stable (generated fa-icons.json)

Icon search works again

What’s NOT done / still open ⚠️

UX polish: empty-state (“No icons found”), keyboard nav, auto-focus search, “Load more” behaviour — you explicitly chose to defer.

True “Icon as Logo/Favicon” field type: right now, logo/favicon fields should stay media IDs; icons are best handled as icon fields or shortcode inserts (we avoided mixing types on purpose).

Theme builder + Forms builder: not started yet (you’re starting a new chat for Forms).

If you want, I can turn this into a commit-ready checklist format for your repo history (so each item becomes a commit message + file list).

---
---


## Changelog (by patch batch)
1) 2026-02-16 11:37–11:47 — impartcms-forms-module-patch.zip

Goal: Create the initial Forms module (CRUD + submissions + settings + embed + delivery logging).

Files changed (15)

app/Http/Controllers/Admin/FormAdminController.php

app/Http/Controllers/Admin/FormSubmissionAdminController.php

app/Http/Controllers/Admin/FormSettingsController.php

app/Http/Controllers/FormSubmissionController.php

app/Models/FormSubmission.php

database/migrations/2026_02_16_000001_add_delivery_fields_to_form_submissions_table.php

resources/views/admin/forms/index.blade.php

resources/views/admin/forms/edit.blade.php

resources/views/admin/forms/settings.blade.php

resources/views/admin/forms/submissions/index.blade.php

resources/views/admin/forms/submissions/show.blade.php

resources/views/cms/forms/embed.blade.php

resources/views/layouts/admin.blade.php

resources/views/themes/default/page.blade.php

routes/web.php

Why

Establish the Forms foundation in admin sidebar and front-end embed.

Store submissions + mail delivery results (status/error).

Resolved ✅

Forms admin area exists.

Submissions stored in DB.

Delivery logging fields added.

Not resolved ❌

No drag/drop builder yet.

No pricing/logic UI yet.

Media/icon option picking not integrated yet.

2) 2026-02-16 15:52–16:03 — impartcms-forms-builder-dragdrop-patch.zip

Goal: First pass at a WPForms-style builder (palette → canvas), plus wiring.

Files changed (16)

resources/js/app.js

resources/js/admin/forms-builder.js

package.json

app/Http/Controllers/Admin/FormAdminController.php

app/Http/Controllers/Admin/FormSubmissionAdminController.php

app/Http/Controllers/Admin/FormSettingsAdminController.php

routes/web.php

resources/views/layouts/admin.blade.php

resources/views/admin/forms/index.blade.php

resources/views/admin/forms/edit.blade.php

resources/views/admin/forms/settings.blade.php

resources/views/admin/forms/submissions/index.blade.php

resources/views/admin/forms/submissions/show.blade.php

resources/views/cms/forms/embed.blade.php

app/Http/Controllers/FormSubmissionController.php

app/Support/Cms.php (included in zip but timestamped 2026-02-13; not evidence of a change that day)

Why

Move from JSON-only forms to drag/drop layout.

Begin “page break = wizard” groundwork.

Resolved ✅

Builder UI introduced.

Front-end embed updated to understand more field types (partial).

Not resolved / introduced issues ❌

Drag/drop initialisation was unstable.

Page breaks got “sticky”.

Option editing (cards/select) prone to focus issues.

3) 2026-02-16 16:23 — impartcms-forms-builder-fix1.patch.zip

Goal: Improve builder boot + stabilise embed.

Files changed (subset + folders) (32 entries, key ones below)

resources/js/app.js

resources/js/admin/forms-builder.js

resources/views/cms/forms/embed.blade.php

routes/web.php

Controllers:

app/Http/Controllers/Admin/FormAdminController.php

app/Http/Controllers/Admin/FormSubmissionAdminController.php

app/Http/Controllers/Admin/FormSettingsAdminController.php

app/Http/Controllers/FormSubmissionController.php

Views:

resources/views/admin/forms/*

resources/views/layouts/admin.blade.php

package.json

Why

Builder wasn’t reliably starting depending on load timing.

Embed view needed to be less brittle.

Resolved (partial) ✅

Improved boot behaviour in some cases.

Not resolved ❌

Page break removal still missing.

Focus-jumping while typing persisted.

4) 2026-02-16 17:38–17:43 — impartcms-forms-fix2.zip

Goal: Add FA icon generator + builder/embed tweaks.

Files changed (5)

scripts/generate-fa-icons.mjs

resources/js/admin/forms-builder.js

resources/views/admin/forms/edit.blade.php

resources/views/cms/forms/embed.blade.php

app/Http/Controllers/FormSubmissionController.php

Why

Attempted to auto-generate FA icon lists for the icon picker.

Resolved ✅

Generator script added.

Not resolved / introduced blocker ❌

Generator expected @fortawesome/.../metadata/icons.json which doesn’t exist in your FA version → build failures → icons/builder breakage.

5) 2026-02-16 18:04 — impartcms-fa-icons-generator-fix.zip

Goal: Make generator compatible with newer FA packaging.

Files changed (1)

scripts/generate-fa-icons.mjs

Why

Try to stop build failures due to missing metadata.

Result ⚠️

Improved generator logic, but still fragile depending on the installed FA package layout.

6) 2026-02-16 19:12 — impartcms-forms-fix3.zip

Goal: Prevent icon generation from breaking builds + keep iterating builder.

Files changed (4)

package.json

scripts/generate-fa-icons.mjs

resources/js/admin/forms-builder.js

resources/views/admin/forms/edit.blade.php

Why

Builds were failing; this aimed to stop the pipeline from collapsing.

Resolved (partial) ✅

Reduced build pipeline fragility.

Not resolved ❌

Icons still not reliably rendering (CSS/list loading issues).

Pricing still not implemented as “price library + rules”.

7) 2026-02-16 19:24 — forms-builder-js-syntax-fix.zip

Goal: Fix Vite parse/import-analysis failure in forms-builder.js.

Files changed (1)

resources/js/admin/forms-builder.js

Why

Build errors around invalid JS syntax / template literal parsing.

Resolved ✅

Builder JS became buildable again (in theory).

8) 2026-02-16 20:37 — impartcms-forms-builder-buildfix.zip

Goal: Replace builder JS with a safer version (no fragile template-literal patterns).

Files changed (1)

resources/js/admin/forms-builder.js

Resolved ✅

You confirmed: “form working for now”

Not resolved ❌

Icons still not showing.

Pricing/logic still missing in the way you want.

9) 2026-02-16 21:24 — impartcms-icons-embed-and-picker-fix.zip

Goal: Fix /home crash + align icon picker messaging + ensure FA CSS import.

Files changed (3)

resources/js/app.js

resources/js/admin/icon-library.js

resources/views/cms/forms/embed.blade.php

Why

/home was crashing with: Cannot access offset of type array on array

Icon picker event/payload needed to match your Media picker format.

Ensure FA CSS loads.

Resolved ✅

✅ You confirmed: Home works again

Not resolved ❌

Icons still not rendering end-to-end (likely remaining list-loading and/or CSS/bundle path mismatch).

Pricing rules library still not delivered.

What was resolved vs not (current state)
✅ Resolved

/home embed crash fixed (confirmed by you)

Forms module present + usable “for now”

Vite build no longer blocked by builder JS parsing (after buildfix)

❌ Not fully resolved yet

Icons rendering (admin + media icon grid/picker)

Pricing as you specified:

add multiple ZAR price options

rules/logic choose which price applies

Builder UX gaps:

page breaks add/remove stability

sections/rows robustness

option editor focus stability in all cases

---

# ChatGPT Applied Changes (Cumulative)

> Source: `CHANGELOG_CHATGPT.md` from the latest updated project bundle provided in this session.

# ImpartCMS – ChatGPT Change Log

Timezone: **Africa/Johannesburg (SAST)**

## 2026-02-18 15:08 SAST – Icons + Page rendering improvements

### ✅ Fixed / Improved

#### 1) Media → Icons: Font Awesome thumbnails now render (not just names)
**Problem**: The Icons browser could list icon names/containers, but the icon preview itself was blank.

**Root cause**: The project’s Font Awesome icon list (`resources/js/admin/fa-icons.json`) was stale/incompatible with the current Font Awesome package version.
- The old generator expected `metadata/icons.json`, but Font Awesome v7 ships `icons.yml` and SVGs instead.
- The UI expected `className` + `style` fields, but the stored JSON did not match.

**Fix**:
- Rebuilt the generator to use the **SVG files** shipped with `@fortawesome/fontawesome-free`.
- The icon library now renders **inline SVG** (portable; no font/CSS dependency), with a safe fallback to `<i>`.

#### 2) Added per-icon **Copy Shortcode** button
**Requirement**: Each icon needs its own explicit copy shortcode option.

**Implementation**:
- Every icon card now has a **Copy** button (top-right).
- Clicking the card still performs the selected action (Copy or Select).

#### 3) Icons are now portable across Frontend/Backend/Form cards
**Problem**: When icons were selected for form “cards select / cards multi”, previews depended on Font Awesome fonts being present.

**Fix**:
- Icon selection payload now includes the **inline SVG** for Font Awesome icons.
- Form-builder option previews and form embeds now prefer SVG rendering.
- The CMS `[icon]` shortcode now supports a `data='{}'` JSON payload that may include SVG.

#### 4) Pages now support full-width “blank” template by default
**Requirement**: New pages should be created blank + full width.

**Implementation**:
- New pages default to `template = blank` (unless explicitly set).
- Added a new theme template: `themes/default/page-blank.blade.php`.
- Updated the public PageController to respect `$page->template` if a matching view exists.

#### 5) Page body now renders HTML (instead of escaping it)
**Requirement**: If you paste HTML into a page body, it must **render as HTML** on the frontend.

**Implementation**:
- `Cms::renderContent()` now outputs non-shortcode content as **raw HTML** when enabled.
- Config toggle added: `CMS_ALLOW_RAW_HTML` (default **true**).

> Note: Executing arbitrary PHP from the database is intentionally **not implemented** because it is a major security risk.

---

### 🧩 Files changed

1. **scripts/generate-fa-icons.mjs**
   - Rebuilt generator to scan Font Awesome SVG folders and produce a stable icon index.

2. **resources/js/admin/fa-icons.json**
   - Regenerated: now includes `style`, `className`, and inline `svg` per icon.

3. **resources/js/admin/fa-icon-list.js**
   - Updated to use the new generated icon shape directly (no duplicate-style expansion).

4. **resources/js/admin/icon-library.js**
   - Render FA icons via inline SVG.
   - Added per-icon **Copy** button.
   - Copy action produces a portable `[icon data='...']` shortcode containing SVG.

5. **resources/js/admin/forms-builder.js**
   - Cards option previews: prefer inline SVG for FA.
   - Lucide preview now calls `window.ImpartLucide.render()` for newly inserted icons.

6. **resources/views/components/admin/icon-picker.blade.php**
   - Icon picker preview now supports FA inline SVG when provided.

7. **resources/views/cms/forms/embed.blade.php**
   - Cards option render helper now prefers inline SVG for FA icons.

8. **app/Support/Cms.php**
   - `renderContent()` now supports raw HTML output (config-controlled).
   - `[icon]` supports `data='{}'` payload and inline SVG rendering.

9. **config/cms.php**
   - Added `allow_raw_html` setting (env: `CMS_ALLOW_RAW_HTML`).

10. **app/Http/Controllers/PageController.php**
   - Added template-aware view resolution: `themes.{theme}.page-{template}`.

11. **resources/views/themes/default/page-blank.blade.php**
   - New blank, full-width page template.

12. **app/Http/Controllers/Admin/PageAdminController.php**
   - New pages default to `template=blank` if omitted.

13. **resources/views/admin/pages/create.blade.php**
   - Default template field now pre-fills as `blank`.

---

### 🔍 What was NOT changed (by design)

- **No execution of arbitrary PHP from DB content** (unsafe). If you want server-side logic, we should do it via:
  - Theme templates (`resources/views/themes/...`) or
  - CMS Modules (`modules/`) that register safe shortcodes/components.

---

## 2026-02-18 15:48 SAST – Settings branding: Logo + Favicon + Login logo (Images OR Icons)

### ✅ Added / Improved

#### 1) Settings: Logo can now be an **Image OR Icon**
**Requirement**: In Settings, allow selecting a logo icon (not only an image).

**Implementation**:
- Added an **Icon picker** next to the existing Media picker.
- Selection behaviour:
  - Picking an icon clears the media logo.
  - Picking a media image clears the icon logo.
- Admin sidebar + top admin layout now render the icon logo when selected.

#### 2) Settings: Favicon can now be an **Image OR Icon**
**Requirement**: Allow favicon to be selected from icons too.

**Implementation**:
- Added favicon icon picker in Settings.
- Added a dynamic route: **/favicon.svg** that serves the selected icon as SVG.
- Frontend + Admin + Login layouts now render favicon via:
  - Media favicon if selected, else
  - `/favicon.svg` if an icon favicon is selected.

#### 3) Settings: Login screen logo (Image OR Icon)
**Requirement**: Add a Settings section to change the login screen logo.

**Implementation**:
- Added `auth_logo_*` settings.
- Guest/auth layout now uses:
  - Login logo (if set), else
  - Main site logo (if set), else
  - Default Breeze application logo.

---

### 🧩 Files changed

1. **app/Support/IconRenderer.php**
   - New: server-side renderer for stored icon JSON.
   - Supports FA SVG-first render + lucide placeholders.
   - Includes `svgForFavicon()` for `/favicon.svg` output.

2. **app/Http/Controllers/FaviconController.php**
   - New: serves favicon SVG based on Settings icon selection.

3. **routes/web.php**
   - Added route: `GET /favicon.svg`.

4. **app/Http/Controllers/Admin/SettingsController.php**
   - Added new settings fields:
     - `site_logo_icon_json`, `site_favicon_icon_json`
     - `auth_logo_media_id`, `auth_logo_icon_json`
   - Added sanitisation/validation for icon JSON.
   - Ensures “icon overrides media” behaviour (and vice versa).

5. **resources/views/admin/settings/edit.blade.php**
   - Added icon pickers for Logo + Favicon.
   - Added Login screen logo section (media + icon).

6. **resources/views/layouts/admin.blade.php**
   - Logo now supports icon fallback.
   - Favicon now supports SVG icon favicon.

7. **resources/views/admin/partials/sidebar.blade.php**
   - Admin sidebar logo now supports icon fallback.

8. **resources/views/layouts/app.blade.php**
   - Frontend favicon now supports media or `/favicon.svg`.

9. **resources/views/layouts/guest.blade.php**
   - Login/register pages now use Settings login logo (media/icon), with fallback.
   - Guest favicon now supports media or `/favicon.svg`.

10. **resources/views/layouts/navigation.blade.php**
   - Frontend nav logo now uses Settings logo (media/icon) when set.

11. **app/Http/Controllers/Admin/MediaAdminController.php**
   - Improved “where used” detection for settings media IDs:
     - `site_logo_media_id`, `site_favicon_media_id`, `auth_logo_media_id`.


---

## 2026-02-18 17:21:05 SAST — Branding pickers unified + login logo sizing

### ✅ What was changed

1) **Settings: Use ONE “Choose from Media Library” button for both Images + Icons**
- Removed the separate icon selector UI for **Logo**, **Favicon**, and **Login screen logo**.
- Added a unified picker component that opens the existing Media Library modal and lets you choose **either**:
  - a Media image, **or**
  - an Icon (from the Icons tab).
- Behaviour:
  - Choosing an image clears the icon JSON.
  - Choosing an icon clears the Media ID.
  - Clear button clears both and sets the “*_clear” flag.

2) **Fix: Broken icon picker button (JS showing inside the button)**
- The old icon-picker used inline `onclick="..."` plus template strings containing **double quotes**, which broke the HTML attribute.
- Updated the icon-picker output to use single quotes inside the injected HTML so it can’t break the attribute.

3) **Settings: Login logo size is now configurable**
- Added `auth_logo_size` (px) in Settings.
- Login/register (guest) layout now renders the logo at the configured pixel size (image or icon).

4) **Fix: Missing classes referenced by layouts**
- `\App\Support\IconRenderer` and `FaviconController` were referenced by routes/views but weren’t present in this codebase.
- Added both files so icon rendering + `/favicon.svg` works reliably.

### 🧩 Files edited / added

- **ADDED** `app/Support/IconRenderer.php`
- **ADDED** `app/Http/Controllers/FaviconController.php`
- **UPDATED** `resources/views/admin/media/picker.blade.php` (support `allow=images,icons` to hide unwanted tabs)
- **ADDED** `resources/views/components/admin/media-icon-picker.blade.php` (unified selector)
- **UPDATED** `resources/views/admin/settings/edit.blade.php` (use unified selector + add login logo size field)
- **UPDATED** `app/Http/Controllers/Admin/SettingsController.php` (validate/store `auth_logo_size`)
- **UPDATED** `resources/views/layouts/guest.blade.php` (apply `auth_logo_size`)
- **UPDATED** `resources/views/components/admin/icon-picker.blade.php` (quote-safety fix)

---

## 2026-02-18 17:40:36 SAST — “Copy shortcode” added to Settings icon selections

### ✅ What was changed

- Added a **Copy shortcode** button to the unified **Media/Icon picker component** used in Settings (Logo / Favicon / Login logo).
- When an icon is selected, the component now shows:
  - the **generated shortcode** (portable `[icon data='...']` format), and
  - a **Copy shortcode** button for one-click copying.
- If the stored icon JSON already contains a `shortcode` field (from the Icon Library), that exact shortcode is used. Otherwise, a portable data-shortcode is generated from the icon JSON.

### 🎯 Why

- You asked for a quick way to **copy icon shortcodes** while working in Settings, without needing to go back to the Icon Library page.

### 🧩 Files edited

- **UPDATED** `resources/views/components/admin/media-icon-picker.blade.php` (add shortcode display + copy button)

---

## 2026-02-18 18:07:00 SAST — Shortcodes made human-readable (compact format)

### ✅ What was changed

- Changed all **“Copy shortcode”** actions (Icon Library, Settings icon picker, Forms Builder option icons) to copy the **compact** shortcode format:
  - ` [icon kind="fa" value="fa-solid fa-house" size="24" colour="#4CBB17"] `
  - ` [icon kind="lucide" value="home" size="24" colour="#4CBB17"] `

### 🎯 Why

- The previous default was the **portable** `[icon data='...json...']` format, which is correct but *very long*.
- You asked for the shorter attribute version because it’s easier to read, edit, and reuse — and it’s already supported by the CMS renderer.

### 🧩 Files edited

- **UPDATED** `resources/js/admin/icon-library.js` (copy now returns compact shortcode)
- **UPDATED** `resources/views/components/admin/media-icon-picker.blade.php` (shortcode display/copy now uses compact format)
- **UPDATED** `resources/js/admin/forms-builder.js` (copy now uses compact icon shortcode)

---

## 2026-02-18 18:37:00 SAST — Forms + Users: Trash system + uniform admin actions

### ✅ Added / Improved

#### 1) Forms now have a proper **Trash** flow (like Pages)
**Requirement**: Forms must be deletable via a trash system (restore / delete permanently).

**Implementation**:
- Forms now use **SoftDeletes** (`deleted_at`).
- New routes + screens:
  - **Forms → Trash** list
  - Restore
  - Delete permanently

#### 2) Users now have a proper **Trash** flow (like Pages)
**Requirement**: Users should not be hard-deleted. Provide trash + restore + permanent delete.

**Implementation**:
- Users now use **SoftDeletes** (`deleted_at`).
- New routes + screens:
  - **Users → Trash** list
  - Restore
  - Delete permanently
- Safety rules preserved:
  - You can’t trash yourself.
  - You can’t trash the last admin.
  - You can’t permanently delete the last active admin.

#### 3) Forms + Users lists now match the **Pages** action/link styling
**Requirement**: Backend actions should be uniform. Trash/delete should be red. Apply primary should be black.

**Implementation**:
- Forms list rebuilt to match the Pages/Users table styling.
- Row actions are now simple text links (Edit / Trash, etc.) like Pages.
- Added a **Trash** button in the header for both Forms and Users.
- Forms list got tabs (All / Active / Inactive) + consistent filter bar.

#### 4) Performance: removed N+1 submissions counting on Forms list
**Fix**: Forms list now uses `withCount('submissions')` instead of per-row queries.

---

### 🧩 Files changed

1. **app/Models/Form.php**
   - Added `SoftDeletes` + `deleted_at` cast.

2. **app/Models/User.php**
   - Added `SoftDeletes` + `deleted_at` cast.

3. **database/migrations/2026_02_18_170000_add_deleted_at_to_forms_table.php**
   - Adds `deleted_at` to `forms`.

4. **database/migrations/2026_02_18_170001_add_deleted_at_to_users_table.php**
   - Adds `deleted_at` to `users`.

5. **routes/web.php**
   - Added `formTrash` + `userTrash` binders.
   - Added Forms/User trash routes (trash / restore / force delete).

6. **app/Http/Controllers/Admin/FormAdminController.php**
   - Added `trash()`, `restore()`, `forceDestroy()`.
   - `destroy()` now moves to trash.
   - Added list filtering/sorting + `withCount('submissions')`.

7. **app/Http/Controllers/Admin/UserAdminController.php**
   - Added `trash()`, `restore()`, `forceDestroy()`.
   - `destroy()` now moves to trash.

8. **resources/views/admin/forms/index.blade.php**
   - Rebuilt UI to match Pages/Users.
   - Added tabs, consistent filters, and row actions.

9. **resources/views/admin/forms/edit.blade.php**
   - Delete button → **Move to Trash** (red).

10. **resources/views/admin/forms/trash.blade.php**
   - New trash view.

11. **resources/views/admin/users/index.blade.php**
   - Added header Trash link + row Trash action.

12. **resources/views/admin/users/edit.blade.php**
   - Delete button → **Move to Trash**.
   - Text updated to match trash flow.

13. **resources/views/admin/users/trash.blade.php**
   - New trash view.

14. **resources/views/admin/pages/trash.blade.php**
   - Fixed invalid HTML escaping in the error block.

---

### ▶️ Required command after update

Run migrations once:

```bash
php artisan migrate
```

---

## 2026-02-18 19:10 SAST — Restore Forms SMTP/Brevo settings + fix form shortcode submit URL

### ✅ What you asked for
1) Bring back **SMTP settings** + **Brevo** option under **Forms → Settings**.
2) Ensure the **form shortcode** works end-to-end (embed + submit), using the standard format:
   - `[form slug="contact"]`
   - `[form slug="contact" to="hello@example.com"]`

### ✅ What was changed

#### A) Forms settings: SMTP + Brevo support
Added provider selection and stored settings:
- Provider: `env` (use .env), `smtp` (override), `brevo` (API)
- SMTP override fields: host/port/username/password/encryption
- Brevo API: api-key

Secrets are stored encrypted at rest (DB) using Laravel Crypt.

#### B) Email sending
Introduced a small mail dispatcher so Forms can send via:
- Laravel default mailer (`env`)
- Custom SMTP override (`smtp`)
- Brevo Transactional API (`brevo`)

#### C) Form embed submit URL
Fixed the submit URL generation:
Public route uses `{form:slug}` so we now always pass the **slug** explicitly.

### 🧩 Files changed
1. **resources/views/cms/forms/embed.blade.php**
   - Fixed `route('forms.submit', ...)` to pass `['form' => $form->slug]`.

2. **app/Models/Setting.php**
   - Added `setSecret()` and `getSecret()` for encrypted settings values.

3. **app/Support/FormMailer.php** *(new)*
   - Added provider-aware mail sending for Forms (env/smtp/brevo).

4. **app/Http/Controllers/FormSubmissionController.php**
   - Refactored to use `FormMailer` instead of sending directly.

5. **app/Http/Controllers/Admin/FormSettingsAdminController.php**
   - Added SMTP + Brevo settings storage + validation.

6. **resources/views/admin/forms/settings.blade.php**
   - Restored UI for SMTP + Brevo selection/settings.
   - Added shortcode usage tip.

### ▶️ Required commands after update

No migrations needed.

If you are running a built asset setup, do a standard refresh:

```bash
php artisan optimize:clear
```

---

## ✅ 2026-02-18 19:31:33 SAST — Homepage dropdown label + Maintenance mode (Settings-driven)

### 🎯 Why
- The Homepage selector was displaying “Title (/slug)” and you wanted **only the page name**.
- You wanted a CMS-level **Maintenance mode** toggle with a selectable “maintenance landing page”, so that **all public routes redirect** to that page while enabled.

### ✅ What changed
#### A) Homepage dropdown label
- Updated the Homepage dropdown to show **only** the page title.

#### B) Maintenance mode
- Added Settings fields:
  - **Enable maintenance mode** (checkbox)
  - **Maintenance page** (published pages dropdown)
- When enabled:
  - All **public GET/HEAD** requests redirect to the maintenance page.
  - Admin users (can:access-admin) can still browse the site normally.
  - Admin + auth routes and static assets are bypassed.
  - Non-GET requests return **503** to avoid weird redirect loops.

### 🧩 Files changed
1. **resources/views/admin/settings/edit.blade.php**
   - Homepage dropdown now shows only the page title.
   - Added Maintenance mode section (enable + page select).

2. **app/Http/Controllers/Admin/SettingsController.php**
   - Added maintenance settings read/write + validation.

3. **app/Http/Middleware/CmsMaintenanceMode.php** *(new)*
   - Enforces Settings-driven maintenance redirects for public routes.

4. **bootstrap/app.php**
   - Registered `CmsMaintenanceMode` into the `web` middleware stack.

---

## ✅ 2026-02-18 19:57:02 SAST — Forms email CC/BCC (defaults + shortcode overrides)

### 🎯 Why
- You wanted proper **CC** and **BCC** support for form notifications.
- You required the ability to **manually override** CC/BCC per embed instance (not only global settings).

### ✅ What changed
#### A) Global defaults (Forms → Settings)
- Added **Default CC (CSV)** and **Default BCC (CSV)** fields.
- These apply when the shortcode does not specify `cc=` / `bcc=`.

#### B) Manual overrides via shortcode
- Extended the form shortcode to accept:
  - `cc="..."`
  - `bcc="..."`
- Example:
  - `[form slug="contact" to="hello@example.com" cc="sales@example.com" bcc="audit@example.com"]`

#### C) Delivery support across providers
- CC/BCC is now applied for:
  - Laravel env mailer
  - Custom SMTP override
  - Brevo Transactional API

### 🧩 Files changed
1. **app/Http/Controllers/Admin/FormSettingsAdminController.php**
   - Added read/write/validation for `forms_default_cc` and `forms_default_bcc`.

2. **resources/views/admin/forms/settings.blade.php**
   - Added CC/BCC inputs.
   - Updated shortcode examples.

3. **app/Support/Cms.php**
   - Shortcode parser now supports `cc` and `bcc` attributes.

4. **resources/views/cms/forms/embed.blade.php**
   - Added hidden fields `_impart_cc` and `_impart_bcc` for overrides.

5. **app/Http/Controllers/FormSubmissionController.php**
   - Excludes cc/bcc override fields from submission payload.
   - Resolves CC/BCC (override → global default).
   - Passes CC/BCC through to mailer.

6. **app/Support/FormMailer.php**
   - Added CC/BCC support to env/smtp/brevo delivery.

### ▶️ Required commands after update

No migrations needed.

If anything looks cached:

```bash
php artisan optimize:clear
```

---

## 2026-02-20 12:31 (Africa/Johannesburg) — Shortcodes + front-end favicon fix

### What was broken
- **Shortcodes were not rendering on some Pages**, especially when the Page body was saved as a **full HTML document** (`<!doctype html>...`). Those pages were returned as-is, so `[icon ...]` / `[form ...]` stayed as raw text.
- **Favicon changes in Settings were only reflected in the admin/backend**, while the front-end continued to show Laravel’s default favicon.

### What I changed
- **Enabled shortcode parsing for full HTML pages** while keeping the HTML intact (no escaping).
- **Applied notice bar style settings** (`notice_bg_colour`, `notice_height`) to full HTML injection (previously hard-coded).
- **Added front-end favicon tags** to theme templates, and also inject favicon tags into full HTML pages.
- **Added cache-busting for favicons** (`?v=` hash) so browsers refresh when the favicon changes.

### Files edited
- `app/Support/Cms.php`
- `app/Http/Controllers/PageController.php`
- `resources/views/themes/default/page.blade.php`
- `resources/views/themes/default/page-blank.blade.php`
