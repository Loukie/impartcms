<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        // Be tolerant: $seo may be null if relation not loaded for some reason.
        $title = $seo?->meta_title ?? $page->title ?? config('app.name');
        $description = $seo?->meta_description ?? config('cms.default_meta_description', '');
        $canonical = $seo?->canonical_url ?? url()->current();
        $robots = $seo?->robots ?? 'index,follow';

        // Open Graph fallbacks
        $ogTitle = $seo?->og_title ?? $title;
        $ogDescription = $seo?->og_description ?? $description;
        $ogImage = $seo?->og_image_url ?? config('cms.default_og_image_url', null);
        $ogType = $seo?->og_type ?? 'website';

        // Twitter fallbacks
        $twTitle = $seo?->twitter_title ?? $ogTitle;
        $twDescription = $seo?->twitter_description ?? $ogDescription;
        $twImage = $seo?->twitter_image_url ?? $ogImage;

        // twitter:card depends on whether we have an image
        $twCard = !empty($twImage) ? 'summary_large_image' : 'summary';

        // Notice bar
        $noticeEnabled = ((string) (\App\Models\Setting::get('notice_enabled', '0') ?? '0')) === '1';
        $noticeMode = (string) (\App\Models\Setting::get('notice_mode', 'text') ?? 'text');
        $noticeText = (string) (\App\Models\Setting::get('notice_text', '') ?? '');
        $noticeHtml = (string) (\App\Models\Setting::get('notice_html', '') ?? '');
        $noticeLinkText = (string) (\App\Models\Setting::get('notice_link_text', '') ?? '');
        $noticeLinkUrl = (string) (\App\Models\Setting::get('notice_link_url', '') ?? '');
        $noticeBgColour = (string) (\App\Models\Setting::get('notice_bg_colour', '#111827') ?? '#111827');
        $noticeHeight = (int) (\App\Models\Setting::get('notice_height', '44') ?? 44);
        if ($noticeHeight < 24) $noticeHeight = 24;
        if ($noticeHeight > 200) $noticeHeight = 200;
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $noticeBgColour)) {
            $noticeBgColour = '#111827';
        }

        // Auto-pick a readable text colour based on background luminance (dark bg => white text; light bg => dark text)
        $noticeTextColour = '#ffffff';
        $hex = ltrim($noticeBgColour, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $lum = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
            if ($lum > 0.62) {
                $noticeTextColour = '#111827';
            }
        }


        // Header/footer blocks (supports shortcodes)
        $headerBlockHtml = (string) (\App\Support\LayoutBlockRenderer::headerRaw($page) ?? '');
        $footerBlockHtml = (string) (\App\Support\LayoutBlockRenderer::footerRaw($page) ?? '');

        // Only load the app bundle when we detect shortcodes that need JS/CSS.
        // Scan body + header/footer blocks + notice.
        $body = is_string($page->body ?? null) ? (string) $page->body : '';
        $scan = $body . "\n" . $headerBlockHtml . "\n" . $footerBlockHtml . "\n" . $noticeText . "\n" . $noticeHtml . "\n" . $noticeLinkText;
        $hasIconShortcodes = $scan !== '' && str_contains($scan, '[icon');
        $hasFormShortcodes = $scan !== '' && str_contains($scan, '[form');


        // Favicon (front-end uses theme templates, so set it here too)
        $faviconPath = (string) (\App\Models\Setting::get('site_favicon_path', null) ?? '');
        $faviconMediaId = (int) (\App\Models\Setting::get('site_favicon_media_id', '0') ?? 0);
        $faviconIconJson = (string) (\App\Models\Setting::get('site_favicon_icon_json', null) ?? '');

        $faviconUrl = null;
        if ($faviconMediaId > 0) {
            $f = \App\Models\MediaFile::query()->whereKey($faviconMediaId)->first();
            if ($f && (method_exists($f, 'isImage') ? $f->isImage() : false)) {
                $faviconUrl = $f->url;
            } elseif ($f && (is_string($f->mime_type ?? null) && str_starts_with((string) $f->mime_type, 'image/'))) {
                $faviconUrl = $f->url;
            }
        }

        if (!$faviconUrl && $faviconPath !== '') {
            $faviconUrl = asset('storage/' . ltrim($faviconPath, '/'));
        }

        $faviconSvgUrl = (!$faviconUrl && $faviconIconJson !== '') ? route('favicon.svg') : null;
        $faviconBust = substr(sha1((string) ($faviconUrl ?? '') . '|' . $faviconMediaId . '|' . $faviconPath . '|' . $faviconIconJson), 0, 12);

        $faviconHref = null;
        $faviconType = null;
        if ($faviconUrl) {
            $faviconHref = $faviconUrl . (str_contains($faviconUrl, '?') ? '&' : '?') . 'v=' . $faviconBust;
        } elseif ($faviconSvgUrl) {
            $faviconHref = $faviconSvgUrl . (str_contains($faviconSvgUrl, '?') ? '&' : '?') . 'v=' . $faviconBust;
            $faviconType = 'image/svg+xml';
        }
    @endphp

    <title>{{ $title }}</title>

    @if($faviconHref)
        <link rel="icon" @if($faviconType)type="{{ $faviconType }}" @endif href="{{ $faviconHref }}">
        <link rel="shortcut icon" href="{{ $faviconHref }}">
    @endif

    @if($hasIconShortcodes || $hasFormShortcodes)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @if($description !== '')
        <meta name="description" content="{{ $description }}">
    @endif

    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="{{ $robots }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $ogTitle }}">
    @if($ogDescription !== '')
        <meta property="og:description" content="{{ $ogDescription }}">
    @endif
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="{{ $twCard }}">
    <meta name="twitter:title" content="{{ $twTitle }}">
    @if($twDescription !== '')
        <meta name="twitter:description" content="{{ $twDescription }}">
    @endif
    @if(!empty($twImage))
        <meta name="twitter:image" content="{{ $twImage }}">
    @endif

    {{-- Custom scripts (HEAD) + Custom CSS (late head for override) --}}
    {!! \App\Support\CustomSnippetRenderer::renderScripts('head', $page) !!}
    {!! \App\Support\CustomSnippetRenderer::renderCss($page) !!}

    @if($noticeEnabled)
        <style>
            :root{
                --notice-bar-h: {{ $noticeHeight }}px;
                --notice-bar-min-h: {{ $noticeHeight }}px;
                --notice-bar-bg: {{ $noticeBgColour }};
                --notice-bar-fg: {{ $noticeTextColour }};
            }
            body{padding-top:var(--notice-bar-h);}
            #site-notice-bar{
                position:fixed;top:0;left:0;right:0;z-index:999999;
                background:var(--notice-bar-bg);
                color:var(--notice-bar-fg);
                min-height:var(--notice-bar-min-h);
                padding:0 14px;
                font-size:14px;
                line-height:1.2;
                display:flex;
                align-items:center;
                transition:opacity .25s ease,transform .25s ease;
            }
            #site-notice-bar a{color:inherit;}
            #site-notice-bar.notice-hidden{opacity:0;transform:translateY(-100%);pointer-events:none;}
        </style>
    @endif
</head>

<body style="margin:0;padding:0;font-family:system-ui;">
    {!! \App\Support\CustomSnippetRenderer::renderScripts('body', $page) !!}

    @if($noticeEnabled)
        <div id="site-notice-bar">
            <div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;">
                @if($noticeMode === 'html')
                    @cmsContent($noticeHtml, $page)
                @else
                    @cmsContent($noticeText, $page)
                    @if($noticeLinkText !== '' && $noticeLinkUrl !== '')
                        <a href="{{ $noticeLinkUrl }}" style="color:inherit;text-decoration:underline;font-weight:600;">
                            @cmsContent($noticeLinkText, $page)
                        </a>
                    @endif
                @endif
            </div>
        </div>
    @endif

    @if(trim($headerBlockHtml) !== '')
        @cmsContent($headerBlockHtml, $page)
    @endif

    {{-- Blank + full width template: you control layout via HTML in the Page body --}}
    @cmsContent($page->body, $page)

    @if(trim($footerBlockHtml) !== '')
        @cmsContent($footerBlockHtml, $page)
    @endif

    {!! \App\Support\CustomSnippetRenderer::renderScripts('footer', $page) !!}

    @if($noticeEnabled)
        <script>
            (function(){
                var bar = document.getElementById('site-notice-bar');
                if(!bar) return;

                var root = document.documentElement;
                var threshold = 8;
                var ticking = false;

                function setSpace(px){
                    root.style.setProperty('--notice-bar-h', String(px) + 'px');
                }

                function show(){
                    bar.classList.remove('notice-hidden');
                    setSpace(bar.offsetHeight || 0);
                }

                function hide(){
                    bar.classList.add('notice-hidden');
                    setSpace(0);
                }

                function sync(){
                    if(window.scrollY > threshold) hide();
                    else show();
                }

                function onScroll(){
                    if(ticking) return;
                    ticking = true;
                    window.requestAnimationFrame(function(){
                        ticking = false;
                        sync();
                    });
                }

                window.addEventListener('scroll', onScroll, {passive:true});
                window.addEventListener('resize', function(){
                    if(window.scrollY <= threshold){
                        setSpace(bar.offsetHeight || 0);
                    }
                }, {passive:true});

                // initial
                sync();
            })();
        </script>
    @endif
</body>
</html>
