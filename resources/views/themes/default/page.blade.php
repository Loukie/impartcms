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
    @endphp

    <title>{{ $title }}</title>

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
</head>

<body style="max-width:900px;margin:40px auto;font-family:system-ui;padding:0 16px;">
    <header style="margin-bottom:24px;">
        <a href="/" style="text-decoration:none;color:inherit;">
            <strong>{{ config('app.name') }}</strong>
        </a>
    </header>

    <h1>{{ $page->title }}</h1>

    <div>
        @cmsContent($page->body, $page)
    </div>

    <footer style="margin-top:40px;border-top:1px solid #ddd;padding-top:16px;">
        <small>Powered by ImpartCMS</small>
    </footer>
</body>
</html>
