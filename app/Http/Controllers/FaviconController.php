<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\IconRenderer;
use Illuminate\Http\Response;

final class FaviconController
{
    /**
     * Serve a dynamic SVG favicon when Settings -> Favicon uses an icon.
     */
    public function svg(): Response
    {
        $json = Setting::get('site_favicon_icon_json', null);
        $svg = IconRenderer::renderSvgString($json, 64, '#111827');

        if ($svg === '') {
            abort(404);
        }

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=600');
    }
}
