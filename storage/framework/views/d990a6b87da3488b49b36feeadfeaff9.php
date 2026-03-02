<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <?php
            $siteName = \App\Models\Setting::get('site_name', config('app.name', 'Laravel'));

            // Favicon (guest)
            $faviconPath = \App\Models\Setting::get('site_favicon_path', null);
            $faviconMediaId = (int) (\App\Models\Setting::get('site_favicon_media_id', '0') ?? 0);
            $faviconIconJson = \App\Models\Setting::get('site_favicon_icon_json', null);
            $faviconUrl = null;
            if ($faviconMediaId > 0) {
                $f = \App\Models\MediaFile::query()->whereKey($faviconMediaId)->first();
                if ($f && ($f->isImage() || (is_string($f->mime_type ?? null) && str_starts_with($f->mime_type, 'image/')))) {
                    $faviconUrl = $f->url;
                }
            }
            if (!$faviconUrl && !empty($faviconPath)) {
                $faviconUrl = asset('storage/' . $faviconPath);
            }
            $faviconIconUrl = (empty($faviconUrl) && !empty($faviconIconJson)) ? route('favicon.svg') : null;

            // Auth/login logo (prefer auth settings; fallback to site logo)
            $authLogoMediaId = (int) (\App\Models\Setting::get('auth_logo_media_id', '0') ?? 0);
            $authLogoIconJson = \App\Models\Setting::get('auth_logo_icon_json', null);
            $authLogoSize = (int) (\App\Models\Setting::get('auth_logo_size', '80') ?? 80);
            if ($authLogoSize < 24) $authLogoSize = 24;
            if ($authLogoSize > 256) $authLogoSize = 256;

            $siteLogoMediaId = (int) (\App\Models\Setting::get('site_logo_media_id', '0') ?? 0);
            $siteLogoPath = \App\Models\Setting::get('site_logo_path', null);
            $siteLogoIconJson = \App\Models\Setting::get('site_logo_icon_json', null);

            $logoUrl = null;
            $pickMediaId = $authLogoMediaId > 0 ? $authLogoMediaId : $siteLogoMediaId;
            if ($pickMediaId > 0) {
                $m = \App\Models\MediaFile::query()->whereKey($pickMediaId)->first();
                if ($m && $m->isImage()) {
                    $logoUrl = $m->url;
                }
            }
            if (!$logoUrl && !empty($siteLogoPath)) {
                $logoUrl = asset('storage/' . $siteLogoPath);
            }

            $logoIconHtml = '';
            $pickIconJson = !empty($authLogoIconJson) ? $authLogoIconJson : $siteLogoIconJson;
            if (empty($logoUrl) && !empty($pickIconJson)) {
                $logoIconHtml = \App\Support\IconRenderer::renderHtml($pickIconJson, $authLogoSize, '#6b7280');
            }
        ?>

        <title><?php echo e($siteName); ?></title>

        <?php if(!empty($faviconUrl)): ?>
            <link rel="icon" href="<?php echo e($faviconUrl); ?>">
        <?php elseif(!empty($faviconIconUrl)): ?>
            <link rel="icon" type="image/svg+xml" href="<?php echo e($faviconIconUrl); ?>">
        <?php endif; ?>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <?php if(!empty($logoUrl)): ?>
                        <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($siteName); ?> logo" style="width: <?php echo e($authLogoSize); ?>px; height: <?php echo e($authLogoSize); ?>px;" class="object-contain" />
                    <?php elseif(!empty($logoIconHtml)): ?>
                        <span class="inline-flex items-center justify-center" style="width: <?php echo e($authLogoSize); ?>px; height: <?php echo e($authLogoSize); ?>px;"><?php echo $logoIconHtml; ?></span>
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['style' => 'width: '.e($authLogoSize).'px; height: '.e($authLogoSize).'px;','class' => 'fill-current text-gray-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['style' => 'width: '.e($authLogoSize).'px; height: '.e($authLogoSize).'px;','class' => 'fill-current text-gray-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                    <?php endif; ?>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                <?php echo e($slot); ?>

            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\laragon\www\2kocms\resources\views/layouts/guest.blade.php ENDPATH**/ ?>