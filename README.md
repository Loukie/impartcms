# ImpartCMS Patch Pack (Laravel 12 + Breeze)

This pack is an **overlay** you copy on top of a fresh **Laravel 12** project (with **Breeze Blade** installed).
It adds:
- Admin Pages + SEO
- Shortcodes (currently: [form slug="contact"])
- Forms + per-page/per-user recipient rules
- Module loader skeleton
- Starter seed data
- SQL bootstrap (optional)

## Quick install (Laragon)
1) Create a new Laravel 12 project in your Laragon `www` folder:
   - `laravel new impartcms`
   - `cd impartcms`

2) Install Breeze (Blade):
   - `composer require laravel/breeze --dev`
   - `php artisan breeze:install blade`
   - `php artisan migrate`
   - `npm install`
   - `npm run build`

3) Copy this patch pack **over** the project root (merge folders).

4) Apply the two small manual edits:
   - `bootstrap/providers.php` → add: `App\Providers\CmsServiceProvider::class,`
   - `app/Providers/AppServiceProvider.php` → add Gate `access-admin` (see `patch_notes/APP_SERVICE_PROVIDER_GUARD_SNIPPET.txt`)

5) Migrate + seed:
   - `php artisan migrate`
   - `php artisan db:seed`

6) Make yourself admin:
   - Register a user
   - Then in DB set `users.is_admin = 1`
     OR use tinker:
     - `php artisan tinker`
     - `$u = \App\Models\User::first(); $u->is_admin = true; $u->save();`

7) Visit:
   - `/` for homepage
   - `/admin` for admin pages list (requires admin)

## Optional SQL
- `impartcms_bootstrap.sql` creates the CMS tables + inserts a starter page/form.
- It also includes `ALTER TABLE users ADD COLUMN is_admin ...`
  (this assumes Laravel's default `users` table already exists).

## Git workflow reminder
Before each major change:
- `git add .`
- `git commit -m "..."` (describe what changed)
- `git push origin main`

