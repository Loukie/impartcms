<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo->meta_title ?? $page->title }}</title>
    @if($seo?->meta_description)
        <meta name="description" content="{{ $seo->meta_description }}">
    @endif
    @if($seo?->canonical_url)
        <link rel="canonical" href="{{ $seo->canonical_url }}">
    @endif
    @if($seo?->robots)
        <meta name="robots" content="{{ $seo->robots }}">
    @endif
</head>
<body style="max-width:900px;margin:40px auto;font-family:system-ui;padding:0 16px;">
    <header style="margin-bottom:24px;">
        <a href="/" style="text-decoration:none;color:inherit;"><strong>{{ config('app.name') }}</strong></a>
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
