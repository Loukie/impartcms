@php
    /** @var \App\Models\Page|null $page */
    /** @var \App\Models\SeoMeta|null $seo */

    $seo = $seo ?? ($page->seo ?? null);

    $title = $seo->meta_title ?? ($page->title ?? config('app.name'));
    $description = $seo->meta_description ?? config('cms.default_meta_description', '');
    $canonical = $seo->canonical_url ?? url()->current();
    $robots = $seo->robots ?? 'index,follow';

    $ogTitle = $seo->og_title ?? $title;
    $ogDescription = $seo->og_description ?? $description;
    $ogImage = $seo->og_image_url ?? config('cms.default_og_image_url');
    $ogType = $seo->og_type ?? 'website';

    $twTitle = $seo->twitter_title ?? $ogTitle;
    $twDescription = $seo->twitter_description ?? $ogDescription;
    $twImage = $seo->twitter_image_url ?? $ogImage;
@endphp

<title>{{ $title }}</title>

@if($description !== '')
    <meta name="description" content="{{ $description }}">
@endif

<link rel="canonical" href="{{ $canonical }}">

<meta name="robots" content="{{ $robots }}">

<meta property="og:title" content="{{ $ogTitle }}">
@if($ogDescription !== '')
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonical }}">
@if(!empty($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif

<meta name="twitter:card" content="{{ !empty($twImage) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $twTitle }}">
@if($twDescription !== '')
    <meta name="twitter:description" content="{{ $twDescription }}">
@endif
@if(!empty($twImage))
    <meta name="twitter:image" content="{{ $twImage }}">
@endif
