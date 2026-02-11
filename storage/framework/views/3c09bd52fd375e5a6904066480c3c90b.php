<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo e(config('app.name', 'ImpartCMS')); ?></title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-gray-100">
<div class="min-h-screen flex">
    
    <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
        <div class="px-4 py-4 border-b border-white/10">
            <div class="text-lg font-semibold"><?php echo e(config('app.name', 'ImpartCMS')); ?></div>
            <div class="text-xs text-white/70 mt-1">Admin</div>
        </div>

        <nav class="p-3 space-y-1">
            
            <a href="<?php echo e(route('dashboard')); ?>"
               class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 <?php echo e(request()->routeIs('dashboard') ? 'bg-white/10' : ''); ?>">
                Dashboard
            </a>

            
            <a href="<?php echo e(url('/')); ?>" target="_blank"
               class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10">
                View site
            </a>

            <div class="my-3 border-t border-white/10"></div>

            
            <a href="<?php echo e(route('admin.pages.index')); ?>"
               class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 <?php echo e(request()->routeIs('admin.pages.*') ? 'bg-white/10' : ''); ?>">
                Pages
            </a>

            
            <?php if(\Illuminate\Support\Facades\Route::has('admin.settings.edit')): ?>
                <a href="<?php echo e(route('admin.settings.edit')); ?>"
                   class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 <?php echo e(request()->routeIs('admin.settings.*') ? 'bg-white/10' : ''); ?>">
                    Settings
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    
    <div class="flex-1 min-w-0">
        
        <header class="bg-white border-b border-gray-200">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Logged in as <span class="font-semibold text-gray-900"><?php echo e(Auth::user()->name); ?></span>
                </div>

                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-sm font-semibold text-gray-700 hover:text-gray-900">
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