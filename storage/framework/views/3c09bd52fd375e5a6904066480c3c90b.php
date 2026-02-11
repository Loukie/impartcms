<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<?php
    /**
     * Admin branding
     * - If no logo: show site name text
     * - If logo: show logo-only by default
     * - Optional setting: allow logo + text
     */
    $siteName = \App\Models\Setting::get('site_name', config('app.name', 'ImpartCMS'));
    $logoPath = \App\Models\Setting::get('site_logo_path', null);
    $showNameWithLogo = (bool) ((int) \App\Models\Setting::get('admin_show_name_with_logo', '0'));

    $hasLogo = !empty($logoPath);
    $showText = !$hasLogo || $showNameWithLogo;
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo e($siteName); ?> Admin</title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-slate-50">
<div class="min-h-screen flex">
    
    <aside class="w-64 bg-slate-950 text-white flex-shrink-0 border-r border-white/5">
        <div class="px-4 py-4 border-b border-white/10">
            <a href="<?php echo e(route('dashboard')); ?>"
               class="flex items-center gap-3 <?php echo e($showText ? '' : 'justify-center'); ?>">
                <?php if($hasLogo): ?>
                    <img src="<?php echo e(asset('storage/' . $logoPath)); ?>"
                         alt="<?php echo e($siteName); ?> logo"
                         class="h-8 w-auto">
                <?php endif; ?>

                <?php if($showText): ?>
                    <div class="min-w-0">
                        <div class="text-base font-semibold tracking-tight truncate"><?php echo e($siteName); ?></div>
                        <div class="text-xs text-white/60 mt-0.5">Admin</div>
                    </div>
                <?php else: ?>
                    <span class="sr-only"><?php echo e($siteName); ?> Admin</span>
                <?php endif; ?>
            </a>
        </div>

        <?php
            $linkBase = 'group flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition';
            $linkInactive = 'text-white/80 hover:text-white hover:bg-white/10';
            $linkActive = 'bg-white/10 text-white';
        ?>

        <nav class="p-3 space-y-1">
            
            <a href="<?php echo e(route('dashboard')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('dashboard') ? $linkActive : $linkInactive); ?>">
                <span>Dashboard</span>
            </a>

            
            <a href="<?php echo e(url('/')); ?>" target="_blank" rel="noopener"
               class="<?php echo e($linkBase); ?> <?php echo e($linkInactive); ?>">
                <span>View site</span>
            </a>

            <div class="my-3 border-t border-white/10"></div>

            
            <a href="<?php echo e(route('admin.pages.index')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.pages.*') ? $linkActive : $linkInactive); ?>">
                <span>Pages</span>
            </a>

            
            <a href="<?php echo e(route('admin.users.index')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.users.*') ? $linkActive : $linkInactive); ?>">
                <span>Users</span>
            </a>

            
            <?php if(\Illuminate\Support\Facades\Route::has('admin.settings.edit')): ?>
                <a href="<?php echo e(route('admin.settings.edit')); ?>"
                   class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('admin.settings.*') ? $linkActive : $linkInactive); ?>">
                    <span>Settings</span>
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    
    <div class="flex-1 min-w-0">
        
        <header class="bg-white/80 backdrop-blur border-b border-slate-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-slate-600">
                    Logged in as <span class="font-semibold text-slate-900"><?php echo e(Auth::user()->name); ?></span>
                </div>

                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                        Log out
                    </button>
                </form>
            </div>

            <?php if(isset($header)): ?>
                <div class="px-6 pb-4">
                    <?php echo e($header); ?>

                </div>
            <?php endif; ?>
        </header>

        <main class="px-6 py-6">
            <?php echo e($slot); ?>

        </main>
    </div>
</div>
</body>
</html>
<?php /**PATH C:\laragon\www\2kocms\resources\views/layouts/admin.blade.php ENDPATH**/ ?>