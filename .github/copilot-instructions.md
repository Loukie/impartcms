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

Frontend design defaults (non-negotiable)
- Default role for frontend tasks: You are a senior UI/UX Design Director + Frontend Engineer.
- You must replicate the reference site's design language when a reference/clone exists, not invent a safe template.
- Primary objective: produce a premium, modern, professional UI that clearly matches the reference clone style.
- If output looks template-like, it is a failed result and must be revised.
- Treat frontend tasks as Design Director work by default: output must look premium, modern, intentional, and brand-specific.
- Do not ship generic/boilerplate navs, heroes, cards, spacing, or typography unless explicitly requested.
- When a reference/clone/screenshot exists, prioritise replicating its design language (layout rhythm, contrast, navigation behavior, button treatment, density, mood) over inventing a safe template.
- If output still looks template-like, revise before finalising.

Required implementation behavior for frontend work
- Define design tokens (`:root` CSS variables) for colors, type scale, spacing, radii, and shadows.
- Ensure responsive quality on desktop and mobile; avoid visual regressions between breakpoints.
- Use purposeful animation only (for example: nav state transitions, section reveals), avoid noisy micro-interactions.
- Keep accessibility and readability intact (contrast, focus visibility, readable text sizes).

Branding defaults for multi-site usage
- Do not hardcode a global brand palette or font family in generated frontend output.
- Derive brand direction from one of these sources (in priority order):
  1. explicit user-provided brand tokens,
  2. existing project/theme styles,
  3. cloned/reference website cues.
- If no brand source exists, propose a concise token set and get confirmation before large visual changes.

Navigation state baseline
- Homepage at top: transparent/overlay nav style.
- Homepage on hover or scroll: transition to solid/light nav style.
- Inner pages: default to solid/light nav style.

Final quality gate for frontend output
- Distinct from starter templates.
- Reference cues are visibly preserved.
- Brand tokens are consistently applied.
- Interaction states work without flicker/jumps.

Site clone execution standard (default behavior)
- If user asks to "clone a site" and "make it modern", treat that as a full quality mandate, not a literal copy.
- Preserve reference structure and brand cues, then modernise spacing, hierarchy, readability, and responsive behavior.
- Ensure the output is fully mobile responsive by default (320px+), not desktop-only.
- Prefer production-safe enhancements over risky rewrites: improve typography scale, section rhythm, CTA clarity, and nav behavior while keeping content intent.
- Eliminate obvious clone artifacts: broken images, placeholder text, inconsistent section spacing, and weak contrast.
- When media import fails, ensure replacement imagery is context-aware and visually consistent with the page section.

Single-command trigger behavior (strict)
- If the user request contains the intent "clone and modernize" plus a reference URL, automatically apply the full clone execution standard and clone acceptance checklist.
- Do not ask the user to restate quality constraints that are already defined in this file.
- Treat minimal prompts as high-authority instructions to deliver a premium, reference-locked redesign in one pass.
- Reject generic starter-template structure, spacing, or typography; regenerate before final output when results look generic.
- Always enforce navigation baseline automatically: homepage top transparent, homepage hover/scroll solid, inner pages solid.
- Return production-ready output only for the requested stack (HTML/CSS/JS or Blade integration).

Clone acceptance checklist (must pass)
- Desktop and mobile screenshots would both look finished and professional.
- Navigation behavior is consistent across homepage and inner pages.
- No broken images/placeholders remain.
- Buttons, headings, and sections have consistent visual system and spacing rhythm.
- Result looks like a polished redesign of the reference, not a generic template.

Quick-start prompt templates
Copy-paste these prompts for consistent high-quality output with minimal input:

**Standard clone + modernize:**
```
Clone and modernize [URL] with reference-lock.
Keep structure/content intent, but redesign with premium hierarchy, spacing rhythm, and typography.
Must be fully responsive (320px+), no placeholders, no generic template patterns.
Nav rules: home top transparent, home hover/scroll solid, inner pages solid.
Return production-ready HTML/CSS/JS only. If generic, revise before final.
```

**With Laravel Blade integration:**
```
Clone and modernize [URL] with reference-lock. Integrate with Laravel Blade.
Keep structure/content intent, redesign with premium hierarchy, spacing, and typography.
Must be fully responsive (320px+), no placeholders, no generic patterns.
Nav rules: home top transparent, home hover/scroll solid, inner pages solid.
Return Blade templates, routes, and controllers. Use existing layouts. If generic, revise before final.
```

Workflow best practices: How to get maximum quality with minimum input
- **Give intent + reference, not detailed specs:** "Clone [URL] and modernize" beats listing every section, color, font, breakpoint.
- **Reference-lock prevents template drift:** Point to an existing site rather than describing "modern professional design."
- **Let the agent execute a complete opinionated pass:** Avoid incremental back-and-forth that drifts toward safe/generic patterns.
- **Use hard constraints, not suggestions:** "No generic patterns" and "If generic, revise" force quality gates.
- **Best result pattern:** Clear intent + reference URL + constraints → get out of the way → receive complete production-quality output.
- **Avoid:** Micro-managing ("make nav blue, use 18px font, add 20px margin...") — this triggers safe incremental mode instead of opinionated design director mode.

If this file is incomplete or you want examples added (module.json sample, blade snippet examples), tell me which examples to include and I will iterate.
