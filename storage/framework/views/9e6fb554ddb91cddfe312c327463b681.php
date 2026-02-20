<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Welcome to the <?php echo e(\App\Models\Setting::get('site_name', config('app.name', 'ImpartCMS'))); ?> admin.
        </p>
     <?php $__env->endSlot(); ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="text-sm text-gray-600">Quick links</div>
            <div class="mt-3 space-y-2">
                <a class="block underline text-gray-700 hover:text-gray-900" href="<?php echo e(route('admin.pages.index')); ?>">
                    Manage Pages
                </a>
                <a class="block underline text-gray-700 hover:text-gray-900" href="<?php echo e(route('admin.users.index')); ?>">
                    Manage Users
                </a>
                <a class="block underline text-gray-700 hover:text-gray-900" href="<?php echo e(route('admin.media.index')); ?>">
                    Manage Media
                </a>
                <a class="block underline text-gray-700 hover:text-gray-900" href="<?php echo e(route('admin.pages.trash')); ?>">
                    View Trash
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="text-sm text-gray-600">Status</div>
            <div class="mt-3 text-gray-800">
                Admin sidebar is now global ✅
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="text-sm text-gray-600">Next up</div>
            <div class="mt-3 text-gray-800">
                Forms + Modules + SEO output
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $attributes = $__attributesOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $component = $__componentOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__componentOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\2kocms\resources\views/dashboard.blade.php ENDPATH**/ ?>