<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Public page renderer.
     * ✅ Only published pages are reachable publicly.
     * ✅ Trashed pages are excluded by default (SoftDeletes).
     */
    public function show(?string $slug = null): View|Response
    {
        $slug = $slug ? trim($slug, '/') : null;

        $homepageId = (int) (Setting::get('homepage_page_id', 0) ?? 0);

        $page = $slug
            ? Page::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first()
            : ($homepageId
                ? Page::query()
                    ->whereKey($homepageId)
                    ->where('status', 'published')
                    ->first()
                : null);

        // Fallback to legacy flag if settings selection is missing or invalid.
        if (!$slug && !$page) {
            $page = Page::query()
                ->where('is_homepage', true)
                ->where('status', 'published')
                ->first();
        }

        if (!$page) {
            abort(404);
        }

        $page->load('seo');

        return view('themes.' . config('cms.theme') . '.page', [
            'page' => $page,
            'seo'  => $page->seo,
        ]);
    }

    /**
     * Admin-only preview.
     * ✅ Allows viewing drafts and trashed pages without exposing publicly.
     */
    public function preview(Page $pagePreview): Response
    {
        $pagePreview->load('seo');

        return response()
            ->view('themes.' . config('cms.theme') . '.page', [
                'page' => $pagePreview,
                'seo'  => $pagePreview->seo,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
