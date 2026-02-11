<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
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
    ?>

    <title><?php echo e($title); ?></title>

    <?php if($description !== ''): ?>
        <meta name="description" content="<?php echo e($description); ?>">
    <?php endif; ?>

    <link rel="canonical" href="<?php echo e($canonical); ?>">

    <meta name="robots" content="<?php echo e($robots); ?>">

    
    <meta property="og:title" content="<?php echo e($ogTitle); ?>">
    <?php if($ogDescription !== ''): ?>
        <meta property="og:description" content="<?php echo e($ogDescription); ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?php echo e($ogType); ?>">
    <meta property="og:url" content="<?php echo e($canonical); ?>">
    <?php if(!empty($ogImage)): ?>
        <meta property="og:image" content="<?php echo e($ogImage); ?>">
    <?php endif; ?>

    
    <meta name="twitter:card" content="<?php echo e($twCard); ?>">
    <meta name="twitter:title" content="<?php echo e($twTitle); ?>">
    <?php if($twDescription !== ''): ?>
        <meta name="twitter:description" content="<?php echo e($twDescription); ?>">
    <?php endif; ?>
    <?php if(!empty($twImage)): ?>
        <meta name="twitter:image" content="<?php echo e($twImage); ?>">
    <?php endif; ?>
</head>

<body style="max-width:900px;margin:40px auto;font-family:system-ui;padding:0 16px;">
    <header style="margin-bottom:24px;">
        <a href="/" style="text-decoration:none;color:inherit;">
            <strong><?php echo e(config('app.name')); ?></strong>
        </a>
    </header>

    <h1><?php echo e($page->title); ?></h1>

    <div>
        <?php echo app("App\Support\Cms")->renderContent($page->body, $page); ?>
    </div>

    <footer style="margin-top:40px;border-top:1px solid #ddd;padding-top:16px;">
        <small>Powered by ImpartCMS</small>
    </footer>
</body>
</html>
<?php /**PATH C:\laragon\www\2kocms\resources\views/themes/default/page.blade.php ENDPATH**/ ?>