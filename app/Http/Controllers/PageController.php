<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\MediaFile;
use App\Models\Setting;
use App\Support\Cms;
use App\Support\CustomSnippetRenderer;
use App\Support\LayoutBlockRenderer;
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

        // If the page body contains a full HTML document, render it directly.
        // This supports “paste a full HTML file” use-cases without nesting inside a theme.
        $body = is_string($page->body ?? null) ? (string) $page->body : '';
        if ($this->isFullHtmlDocument($body)) {
            return response($this->injectFullDocumentExtras($body, $page))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $page->load('seo');

        return view($this->resolvePageView($page), [
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

        $body = is_string($pagePreview->body ?? null) ? (string) $pagePreview->body : '';
        if ($this->isFullHtmlDocument($body)) {
            return response($this->injectFullDocumentExtras($body, $pagePreview))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return response()
            ->view($this->resolvePageView($pagePreview), [
                'page' => $pagePreview,
                'seo'  => $pagePreview->seo,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function isFullHtmlDocument(string $body): bool
    {
        $hay = strtolower($body);
        if (str_contains($hay, '<!doctype html')) return true;
        if (str_contains($hay, '<html')) return true;
        if (str_contains($hay, '<head') && str_contains($hay, '<body')) return true;
        return false;
    }

    private function injectFullDocumentExtras(string $html, Page $page): string
    {
        $cms = app(Cms::class);

        // Render shortcodes inside full HTML documents too (without escaping HTML).
        // This keeps [icon ...] and [form ...] working even when the page body is a full document.
        $rawHtmlForScan = $html;
        $html = (string) $cms->renderContent($html, $page, true);

        // Snippets
        $headScripts = (string) CustomSnippetRenderer::renderScripts('head', $page);
        $css = (string) CustomSnippetRenderer::renderCss($page);
        $bodyScripts = (string) CustomSnippetRenderer::renderScripts('body', $page);
        $footerScripts = (string) CustomSnippetRenderer::renderScripts('footer', $page);

        // Header/footer blocks (supports shortcodes)
        $headerRaw = (string) (LayoutBlockRenderer::headerRaw($page) ?? '');
        $footerRaw = (string) (LayoutBlockRenderer::footerRaw($page) ?? '');

        $headerMarkup = trim($headerRaw) !== ''
            ? "\n<!-- Header Block -->\n" . (string) $cms->renderContent($headerRaw, $page) . "\n"
            : '';

        $footerMarkup = trim($footerRaw) !== ''
            ? "\n<!-- Footer Block -->\n" . (string) $cms->renderContent($footerRaw, $page) . "\n"
            : '';

        // Notice bar (supports shortcodes)
        $noticeEnabled = ((string) (Setting::get('notice_enabled', '0') ?? '0')) === '1';
        $noticeMode = (string) (Setting::get('notice_mode', 'text') ?? 'text');
        $noticeTextRaw = (string) (Setting::get('notice_text', '') ?? '');
        $noticeHtmlRaw = (string) (Setting::get('notice_html', '') ?? '');
        $noticeLinkTextRaw = (string) (Setting::get('notice_link_text', '') ?? '');
        $noticeLinkUrl = (string) (Setting::get('notice_link_url', '') ?? '');

        $noticeContent = '';
        if ($noticeEnabled) {
            if ($noticeMode === 'html') {
                $noticeContent = trim($noticeHtmlRaw) !== ''
                    ? (string) $cms->renderContent($noticeHtmlRaw, $page)
                    : '';
            } else {
                $noticeContent = trim($noticeTextRaw) !== ''
                    ? (string) $cms->renderContent($noticeTextRaw, $page)
                    : '';

                if ($noticeLinkTextRaw !== '' && $noticeLinkUrl !== '') {
                    $safeUrl = e($noticeLinkUrl);
                    $linkText = (string) $cms->renderContent($noticeLinkTextRaw, $page);
                    $noticeContent .= ' <a href="' . $safeUrl . '" style="text-decoration:underline;font-weight:600;">' . $linkText . '</a>';
                }
            }
        }

        $noticeMarkup = '';
        $noticeHeadCss = '';
        $noticeFooterJs = '';

        if ($noticeEnabled) {
            $noticeBgColour = (string) (Setting::get('notice_bg_colour', '#111827') ?? '#111827');
            $noticeHeight = (int) (Setting::get('notice_height', '44') ?? 44);
            if ($noticeHeight < 24) $noticeHeight = 24;
            if ($noticeHeight > 200) $noticeHeight = 200;
            if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $noticeBgColour)) {
                $noticeBgColour = '#111827';
            }
            $noticeTextColour = $this->pickReadableTextColour($noticeBgColour);

            $noticeHeadCss = "\n<style>\n:root{--notice-bar-h:" . $noticeHeight . "px;}\nbody{padding-top:var(--notice-bar-h);}\n#site-notice-bar{position:fixed;top:0;left:0;right:0;z-index:999999;background:" . $noticeBgColour . ";color:" . $noticeTextColour . ";min-height:" . $noticeHeight . "px;padding:10px 14px;font-size:14px;line-height:1.2;display:flex;align-items:center;justify-content:center;transition:opacity .25s ease,transform .25s ease;}\n#site-notice-bar a{color:inherit;}\n#site-notice-bar.notice-hidden{opacity:0;transform:translateY(-100%);pointer-events:none;}\n</style>\n";

            $noticeMarkup = "\n<div id=\"site-notice-bar\"><div style=\"max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;\">" . $noticeContent . "</div></div>\n";

            $noticeFooterJs = "\n<script>(function(){var bar=document.getElementById('site-notice-bar');if(!bar)return;var root=document.documentElement;var threshold=8;var ticking=false;function setSpace(px){root.style.setProperty('--notice-bar-h',String(px)+'px');}function show(){bar.classList.remove('notice-hidden');setSpace(bar.offsetHeight||0);}function hide(){bar.classList.add('notice-hidden');setSpace(0);}function sync(){if(window.scrollY>threshold)hide();else show();}function onScroll(){if(ticking)return;ticking=true;window.requestAnimationFrame(function(){ticking=false;sync();});}window.addEventListener('scroll',onScroll,{passive:true});window.addEventListener('resize',function(){if(window.scrollY<=threshold){setSpace(bar.offsetHeight||0);}},{passive:true});sync();})();</script>\n";
        }

        // If header/footer/notice contain shortcodes, ensure app bundle is available for icons/forms.
        $needsAppBundle = false;
        $scan = $rawHtmlForScan . "\n" . $headerRaw . "\n" . $footerRaw . "\n" . $noticeTextRaw . "\n" . $noticeHtmlRaw . "\n" . $noticeLinkTextRaw;
        if ($scan !== '' && (str_contains($scan, '[icon') || str_contains($scan, '[form'))) {
            $needsAppBundle = true;
        }

        $viteTags = '';
        if ($needsAppBundle) {
            try {
                $viteTags = (string) vite(['resources/css/app.css', 'resources/js/app.js']);
            } catch (\Throwable $e) {
                // If the manifest isn't available, fail silently.
                $viteTags = '';
            }
        }

        $faviconLinks = $this->buildFaviconHeadLinks();

        // Inject into head (before </head>)
        $headInsert = $faviconLinks . $viteTags . $headScripts . $css . $noticeHeadCss;
        if ($headInsert !== '') {
            if (preg_match('/<\/head\s*>/i', $html)) {
                $html = preg_replace('/<\/head\s*>/i', $headInsert . "\n</head>", $html, 1) ?? $html;
            } else {
                $html = $headInsert . "\n" . $html;
            }
        }

        // Inject into body (right after opening <body ...>)
        $bodyInsert = $bodyScripts . $noticeMarkup . $headerMarkup;
        if ($bodyInsert !== '') {
            if (preg_match('/<body\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
                $tag = $m[0][0];
                $pos = $m[0][1] + strlen($tag);
                $html = substr($html, 0, $pos) . "\n" . $bodyInsert . substr($html, $pos);
            } else {
                $html = $bodyInsert . "\n" . $html;
            }
        }

        // Inject before closing </body>
        $footerInsert = $footerMarkup . $footerScripts . $noticeFooterJs;
        if ($footerInsert !== '') {
            if (preg_match('/<\/body\s*>/i', $html)) {
                $html = preg_replace('/<\/body\s*>/i', $footerInsert . "\n</body>", $html, 1) ?? $html;
            } else {
                $html .= "\n" . $footerInsert;
            }
        }

        return $html;
    }

    private function buildFaviconHeadLinks(): string
    {
        $faviconPath = (string) (Setting::get('site_favicon_path', null) ?? '');
        $faviconMediaId = (int) (Setting::get('site_favicon_media_id', '0') ?? 0);
        $faviconIconJson = (string) (Setting::get('site_favicon_icon_json', null) ?? '');

        $faviconUrl = null;
        if ($faviconMediaId > 0) {
            $f = MediaFile::query()->whereKey($faviconMediaId)->first();
            if ($f && (method_exists($f, 'isImage') ? $f->isImage() : false)) {
                $faviconUrl = $f->url;
            } elseif ($f && (is_string($f->mime_type ?? null) && str_starts_with((string) $f->mime_type, 'image/'))) {
                $faviconUrl = $f->url;
            }
        }

        if (!$faviconUrl && $faviconPath !== '') {
            $faviconUrl = asset('storage/' . ltrim($faviconPath, '/'));
        }

        $faviconIconUrl = (!$faviconUrl && $faviconIconJson !== '') ? route('favicon.svg') : null;

        $bust = substr(sha1((string) ($faviconUrl ?? '') . '|' . $faviconMediaId . '|' . $faviconPath . '|' . $faviconIconJson), 0, 12);

        if ($faviconUrl) {
            $href = $faviconUrl . (str_contains($faviconUrl, '?') ? '&' : '?') . 'v=' . $bust;
            $hrefEsc = e($href);
            return "\n" . '<link rel="icon" href="' . $hrefEsc . '">' . "\n" . '<link rel="shortcut icon" href="' . $hrefEsc . '">' . "\n";
        }

        if ($faviconIconUrl) {
            $href = $faviconIconUrl . (str_contains($faviconIconUrl, '?') ? '&' : '?') . 'v=' . $bust;
            $hrefEsc = e($href);
            return "\n" . '<link rel="icon" type="image/svg+xml" href="' . $hrefEsc . '">' . "\n" . '<link rel="shortcut icon" href="' . $hrefEsc . '">' . "\n";
        }

        return '';
    }

    private function pickReadableTextColour(string $bgHex): string
    {
        $hex = ltrim(trim($bgHex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            return '#ffffff';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $lum = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
        return $lum > 0.62 ? '#111827' : '#ffffff';
    }

    private function resolvePageView(Page $page): string
    {
        $theme = (string) config('cms.theme', 'default');
        $base = 'themes.' . $theme . '.page';

        $tpl = trim((string) ($page->template ?? ''));
        if ($tpl === '') {
            return $base;
        }

        // Allow only safe template keys (prevent directory traversal / arbitrary view names)
        $tpl = strtolower($tpl);
        $tpl = preg_replace('/[^a-z0-9\-_]/', '', $tpl) ?: '';
        if ($tpl === '') {
            return $base;
        }

        $candidate = $base . '-' . $tpl;

        return view()->exists($candidate) ? $candidate : $base;
    }
}
