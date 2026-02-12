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
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="<?php echo e(route('admin.settings.update')); ?>" enctype="multipart/form-data" class="space-y-8">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">General</h3>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Site name</label>
                                <input type="text" name="site_name" value="<?php echo e(old('site_name', $siteName)); ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                <p class="mt-1 text-xs text-gray-500">Used across the CMS admin and default fallbacks.</p>
                                <?php $__errorArgs = ['site_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700">Homepage / Landing page</label>
                                <select name="homepage_page_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <option value="">— Select a published page —</option>
                                    <?php $__currentLoopData = $homepagePages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($p->id); ?>" <?php echo e((int) old('homepage_page_id', $homepagePageId) === (int) $p->id ? 'selected' : ''); ?>>
                                            <?php echo e($p->title); ?> (/<?php echo e(ltrim($p->slug, '/')); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">This page will load at <span class="font-mono">/</span>. Only published pages are listed.</p>
                                <?php $__errorArgs = ['homepage_page_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900">Branding</h3>
                            <p class="mt-1 text-xs text-gray-500">Admin sidebar branding rules: if no logo → text. If logo → logo-only by default (optional logo + text).</p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Logo</label>

                                <?php
                                    $hasLogo = !empty($logoMediaUrl) || !empty($logoPath);
                                    $previewUrl = !empty($logoMediaUrl)
                                        ? $logoMediaUrl
                                        : (!empty($logoPath) ? asset('storage/' . $logoPath) : null);
                                ?>

                                <?php if($hasLogo): ?>
                                    <div class="mt-3 flex items-center gap-4">
                                        <img src="<?php echo e($previewUrl); ?>" alt="Site logo"
                                             class="h-10 w-auto rounded bg-white border border-gray-200 p-1">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="remove_logo" value="1"
                                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                            Remove logo
                                        </label>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-4">
                                    <?php if (isset($component)) { $__componentOriginal78e2226de3aca9b0c13f2dda29d8d009 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.media-picker','data' => ['name' => 'site_logo_media_id','label' => 'Choose from Media library','value' => old('site_logo_media_id', $logoMediaId),'previewUrl' => $logoMediaUrl,'accept' => 'images','buttonText' => 'Choose from Media library','help' => 'Using a Media image as your logo will not delete it when removed from Settings.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.media-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'site_logo_media_id','label' => 'Choose from Media library','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('site_logo_media_id', $logoMediaId)),'preview-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($logoMediaUrl),'accept' => 'images','button-text' => 'Choose from Media library','help' => 'Using a Media image as your logo will not delete it when removed from Settings.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009)): ?>
<?php $attributes = $__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009; ?>
<?php unset($__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal78e2226de3aca9b0c13f2dda29d8d009)): ?>
<?php $component = $__componentOriginal78e2226de3aca9b0c13f2dda29d8d009; ?>
<?php unset($__componentOriginal78e2226de3aca9b0c13f2dda29d8d009); ?>
<?php endif; ?>
                                    <?php $__errorArgs = ['site_logo_media_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <input type="file" name="site_logo" accept="image/*"
                                       class="mt-3 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-900 file:text-white hover:file:bg-gray-800">
                                <?php $__errorArgs = ['site_logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <p class="mt-2 text-xs text-gray-500">PNG/JPG. Max 2MB. Recommended height ~40px.</p>
                            </div>

                            <div class="mt-5">
                                <label class="inline-flex items-start gap-3 text-sm text-gray-700">
                                    <input type="checkbox" name="admin_show_name_with_logo" value="1"
                                           <?php echo e(old('admin_show_name_with_logo', $showNameWithLogo) ? 'checked' : ''); ?>

                                           <?php echo e($hasLogo ? '' : 'disabled'); ?>

                                           class="mt-0.5 rounded border-gray-300 text-gray-900 focus:ring-gray-500 disabled:opacity-50">
                                    <span>
                                        <span class="font-medium">Show site name next to logo in admin sidebar</span>
                                        <span class="block text-xs text-gray-500 mt-1">
                                            <?php echo e($hasLogo ? 'Enabled = logo + text. Disabled = logo-only.' : 'Add a logo to enable this option.'); ?>

                                        </span>
                                    </span>
                                </label>
                                <?php $__errorArgs = ['admin_show_name_with_logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/settings/edit.blade.php ENDPATH**/ ?>