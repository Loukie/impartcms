# MANIFEST.md — ImpartCMS (Laravel 12 + Breeze)

Date: 2026-02-13  
Repo: https://github.com/Loukie/impartcms  
Local path: `C:\laragon\www\2kocms`  
Local domain (Laragon): `http://2kocms.test`

---

## 1) Core goal ✅
A fast, secure, modular CMS (WordPress-style admin UX) with:
- Pages (Draft/Publish), admin preview, Trash (soft deletes)
- SEO meta layer (RankMath-style panel)
- Global admin layout/sidebar
- Media library + WordPress-style picker modal
- Users management (admin/member roles)
- Future: Theme builder (Divi/Elementor style) that **never slows front-end**

---

## 2) Auth + Admin access ✅
- Breeze installed (Blade auth scaffolding)
- Admin gate:
  - `app/Providers/AppServiceProvider.php`
  - Gate: `access-admin`
- Admin routes protected by middleware: `auth` + `can:access-admin`

---

## 3) Routing ✅ (critical ordering)
File: `routes/web.php`

- Admin preview (drafts + trashed):
  - `GET /_preview/pages/{pagePreview}` → `PageController@preview`
  - Middleware: `auth` + `can:access-admin`
  - Adds `X-Robots-Tag: noindex, nofollow`
- Admin prefix:
  - `prefix(config('cms.admin_path','admin'))` (default `/admin`)
- Public pages:
  - Catch-all `GET /{slug}` is **last**
  - Homepage resolves via `is_homepage` flag (published only)
- Route model binding:
  - `pagePreview` resolves with `Page::withTrashed()`
  - `pageTrash` resolves with `Page::withTrashed()`

---

## 4) Pages admin ✅ (CRUD + Draft/Publish workflow)
File: `app/Http/Controllers/Admin/PageAdminController.php`

- Draft/Publish based on `request('action')`:
  - Draft: `status=draft`, clears `published_at`
  - Publish: `status=published`, sets `published_at` if missing
- `destroy()` moves page to Trash (soft delete)
- Trash routes:
  - list trashed, restore, force delete
- Homepage selection:
  - WordPress-like “Set as Home”
  - Homepage **protected**: cannot be trashed/deleted while flagged home

Views:
- `resources/views/admin/pages/index.blade.php`
- `resources/views/admin/pages/trash.blade.php`
- `resources/views/admin/pages/create.blade.php`
- `resources/views/admin/pages/edit.blade.php`

---

## 5) Trash system ✅
- Pages use SoftDeletes:
  - Migration adds `deleted_at`
  - `app/Models/Page.php` uses `SoftDeletes`
- Public never sees trashed/drafts
- Admin preview can render trashed/drafts via `_preview` route

---

## 6) SEO meta layer ✅ (data saved + panel UI)
- Page → `seo()` hasOne
- Controller saves SEO fields alongside page
- SERP preview + simple score + checklist exists in admin UI
- NOTE: front-end `<head>` wiring still required to print meta tags in theme

---

## 7) Admin layout + sidebar ✅ (global, single source of truth)
Layout:
- `resources/views/layouts/admin.blade.php`

Component forwarding:
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/components/admin-layout.blade.php`

Sidebar rules:
- Sidebar always visible on admin screens
- Sidebar includes:
  - Dashboard
  - View site (under Dashboard)
  - Pages
  - Media
  - Users
  - Settings
- Sidebar labels have icons (Dashboard/View site/Pages/Media/Users/Settings)

Branding rules (admin sidebar top-left):
- Site name comes from Settings
- If logo is set: show logo-only by default
- Setting exists: “Show site name next to logo” (logo + text)
- “Remove logo” only clears Settings reference (does NOT delete Media file)

---

## 8) Full-width admin layout ✅
- Removed restrictive `max-width: 80rem` for backend pages
- Applies to existing and new admin pages (layout-driven)

---

## 9) Users admin ✅
- List users + show admins
- Create user in backend
- Edit user (role toggle admin/member)
- Send reset link
- Random password generator included for admin-created users

---

## 10) Media library ✅ (uploads + details + usage scanning)
Core:
- Upload images + PDFs (and allowed formats below)
- Auto-organised into `YYYY/MM`
- Media detail screen shows:
  - Public URL
  - Title / alt / caption
  - Where-used scan (page body + SEO image URLs)

Storage:
- Uses `public/storage` symlink
- Media files stored under `storage/app/public/media/YYYY/MM/...`

Allowed upload types (current intent):
- Images: png, jpg, jpeg, webp, avif, svg, ico
- Docs: pdf
- (Fonts tab is being removed per request — see “Current focus” below)

---

## 11) Media picker modal ✅ (WordPress-style)
Goal:
- Any “select media” field opens a modal picker (not a dropdown)
- Same behaviour across Settings/logo, future theme builder, etc.

Picker UI:
- Header: Library / Upload / Cancel
- Right controls: Folder dropdown + Search + Apply + Reset
- Grid shows items with a “Select” button

IMPORTANT:
- We are standardising the picker tabs to match request:
  - ✅ All
  - ✅ Images
  - ✅ Icons (libraries + selectable)
  - ✅ Docs
  - ❌ Fonts (remove)

---

## 12) Icon system ✅ (Selectable, future Theme Builder-ready)
### Requirement (non-negotiable)
Icons must be selectable and stored in a future-proof format for theme builder.

Storage format:
- Option A chosen ✅
- Store selected icon as JSON (string) in the field, e.g.
  - Font Awesome:
    `{"kind":"fa","value":"fa-solid fa-house","size":24,"colour":"#111827"}`
  - Lucide:
    `{"kind":"lucide","value":"home","size":24,"colour":"#111827"}`

Picker “Icons” tab layout:
- Top half: Font Awesome icon grid (Free)
- Bottom half: Lucide icon grid (SVG)
- Search/filter applies to both sections

---

## 13) Shortcodes ✅ (manual insertion)
Icon shortcode supports BOTH formats:
1) Simple attributes:
   - `[icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]`
   - `[icon kind="lucide" value="home" size="24" colour="#111827"]`

2) JSON payload:
   - `[icon data='{"kind":"fa","value":"fa-solid fa-house","size":24,"colour":"#111827"}']`

Planned UX:
- Add shortcode examples/tutorial in Settings (help block)
- Add preview helper in page editor (nice-to-have)

---

## 14) Current focus / known mismatch ⚠️ (why it feels “1 step forward, 2 back”)
You currently see:
- Media page has a **Fonts** tab
- Select-media modal shows **All / Images / Docs** only
- Icons tab shows **0**

Root cause:
- Media index view and media picker modal are separate templates
- Multiple patch zips overwrote different files out-of-sync
- “Icons (0)” currently counts **uploaded SVG/ICO media only**, not icon libraries

Resolution we are enforcing:
- Remove Fonts tab entirely (admin Media + modal)
- Add Icons tab in modal that contains:
  - FA grid (top)
  - Lucide grid (bottom)
- Keep uploaded SVG/ICO under Images (or treat them as normal media), but library-icons are separate

---

## 15) Commands / cache reset ✅
Laravel caches:
- `php artisan optimize:clear`

Rebuild assets:
- `npm run build`

Hard refresh:
- Ctrl + Shift + R

---

## 16) Notes for stability ✅
- Prefer a single consolidated overwrite zip per milestone (avoid stacking partial patches)
- Icons must live in admin bundle only (front-end stays fast)
- Theme builder will reuse the same picker + JSON icon schema


---

## Patch: 2026-02-13 (Media + Settings + Font Awesome)

### Settings
- Removed direct file upload inputs for Logo + Favicon from Settings.
- Logo/Favicon now use **Media library only** via the existing picker modal:
  - Buttons: **Choose from Media Library**, **Upload**, **Clear**
  - Clear uses hidden flags (`site_logo_clear`, `site_favicon_clear`) instead of checkboxes.

### Media
- Media top tabs are now **Images | Icons | Docs** (removed All + Fonts).
- **Icons** tab is now a **Font Awesome browser** (Solid/Regular/Brands) with search + size + colour.
  - In Media page: clicking an icon copies the class (e.g. `fa-solid fa-house`) to clipboard.
  - In picker modal: clicking an icon returns it to the caller (IconPicker).
- Upload bar on Media page is now hidden by default and toggled via **Upload** button (matches modal behaviour).

### Font Awesome
- Icon browser uses dynamic imports from:
  - `@fortawesome/free-solid-svg-icons`
  - `@fortawesome/free-regular-svg-icons`
  - `@fortawesome/free-brands-svg-icons`
- Ensure Vite assets are rebuilt after pulling changes.
