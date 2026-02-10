<?php

namespace App\Http\Controllers;

use App\Models\Page;
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

        $page = $slug
            ? Page::query()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first()
            : Page::query()
                ->where('is_homepage', true)
                ->where('status', 'published')
                ->first();

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
