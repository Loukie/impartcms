<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (string) (Setting::get('maintenance_enabled', '0') ?? '0');
        if ($enabled !== '1') {
            return $next($request);
        }

        // Allow admins to access the site normally while maintenance mode is on.
        // (They can still preview what users see via an incognito window.)
        if (auth()->check() && auth()->user()->can('access-admin')) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $maintenancePageId = (int) (Setting::get('maintenance_page_id', '0') ?? 0);
        $maintenancePage = $maintenancePageId > 0
            ? Page::query()
                ->whereKey($maintenancePageId)
                ->where('status', 'published')
                ->first()
            : null;

        // If no page is configured (or it no longer exists), fail closed with a 503.
        if (!$maintenancePage) {
            return response('Maintenance mode is enabled, but no maintenance page is configured.', 503);
        }

        // We redirect to the homepage (/) so the URL stays clean.
        // Settings will temporarily set the selected maintenance page as the homepage while maintenance is enabled.
        $targetPath = '/';

        // If already on the maintenance page, allow.
        $currentPath = '/' . ltrim($request->path(), '/');
        if ($request->path() === '') {
            $currentPath = '/';
        }

        if ($currentPath === $targetPath) {
            return $next($request);
        }

        // For non-GET requests, do not redirect (prevents odd loops / broken POSTs).
        if (!in_array(strtoupper($request->method()), ['GET', 'HEAD'], true)) {
            return response('Site is in maintenance mode.', 503);
        }

        return redirect($targetPath);
    }

    private function shouldBypass(Request $request): bool
    {
        $adminPath = trim((string) config('cms.admin_path', 'admin'), '/');
        if ($adminPath !== '' && ($request->is($adminPath) || $request->is($adminPath . '/*'))) {
            return true;
        }

        // Auth routes (Breeze)
        if ($request->is('login')
            || $request->is('register')
            || $request->is('forgot-password')
            || $request->is('reset-password/*')
            || $request->is('verify-email')
            || $request->is('email/*')
            || $request->is('confirm-password')
            || $request->is('two-factor-challenge')
        ) {
            return true;
        }

        // Static assets and health
        if ($request->is('storage/*')
            || $request->is('build/*')
            || $request->is('favicon.ico')
            || $request->is('favicon.svg')
            || $request->is('robots.txt')
            || $request->is('sitemap.xml')
            || $request->is('up')
        ) {
            return true;
        }

        return false;
    }
}
