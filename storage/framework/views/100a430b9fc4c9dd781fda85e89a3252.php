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
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media</h2>
                <p class="text-sm text-gray-600 mt-1">Edit file details and manage usage.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.media.index')); ?>"
                   class="text-sm text-gray-600 hover:text-gray-900">
                    Back to Media
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="border rounded-lg bg-slate-50 overflow-hidden">
                                <div class="aspect-video flex items-center justify-center">
                                    <?php if($media->isImage()): ?>
                                        <img src="<?php echo e($media->url); ?>"
                                             alt="<?php echo e($media->alt_text ?? $media->title ?? $media->original_name); ?>"
                                             class="max-h-[420px] w-auto object-contain" />
                                    <?php else: ?>
                                        <div class="text-sm text-slate-700">
                                            <?php echo e(strtoupper(pathinfo($media->original_name, PATHINFO_EXTENSION) ?: 'FILE')); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700">Public URL</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input id="mediaPublicUrl" type="text" readonly
                                           class="w-full rounded-md border-gray-300 text-sm"
                                           value="<?php echo e($media->url); ?>" />
                                    <button type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('mediaPublicUrl').value)"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Copy
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Tip: paste this URL into page body or SEO image fields.</div>
                            </div>

                            <form method="POST" action="<?php echo e(route('admin.media.update', $media)); ?>" class="mt-6">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PUT'); ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Title</label>
                                        <input type="text" name="title" value="<?php echo e(old('title', $media->title)); ?>"
                                               class="mt-1 w-full rounded-md border-gray-300" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Alt text</label>
                                        <input type="text" name="alt_text" value="<?php echo e(old('alt_text', $media->alt_text)); ?>"
                                               class="mt-1 w-full rounded-md border-gray-300" />
                                        <div class="text-xs text-gray-500 mt-1">Used for accessibility + SEO (images only).</div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Caption</label>
                                    <textarea name="caption" rows="3" class="mt-1 w-full rounded-md border-gray-300"><?php echo e(old('caption', $media->caption)); ?></textarea>
                                </div>

                                <div class="mt-6 flex items-center justify-between">
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Save
                                    </button>

                                    
                                    <a href="#media-delete" class="sr-only">Delete</a>
                                </div>
                            </form>

                            <form id="media-delete" method="POST" action="<?php echo e(route('admin.media.destroy', $media)); ?>" class="mt-3 flex justify-end" onsubmit="return confirm('Delete this file?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit"
                                        <?php if(($usage['is_used'] ?? false)): ?> disabled <?php endif; ?>
                                        class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest
                                               <?php echo e(($usage['is_used'] ?? false) ? 'bg-red-200 text-red-700 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-500'); ?>">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-sm font-semibold text-gray-900">File info</h3>

                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Name</dt>
                                    <dd class="text-gray-900 text-right break-all"><?php echo e($media->original_name); ?></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Folder</dt>
                                    <dd class="text-gray-900 text-right"><?php echo e($media->folder); ?></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Type</dt>
                                    <dd class="text-gray-900 text-right"><?php echo e($media->mime_type); ?></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Size</dt>
                                    <dd class="text-gray-900 text-right"><?php echo e(number_format(($media->size_bytes ?? 0) / 1024, 1)); ?> KB</dd>
                                </div>
                                <?php if(!empty($media->width) && !empty($media->height)): ?>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-gray-500">Dimensions</dt>
                                        <dd class="text-gray-900 text-right"><?php echo e($media->width); ?> × <?php echo e($media->height); ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>

                            <hr class="my-5">

                            <h3 class="text-sm font-semibold text-gray-900">Where used</h3>
                            <div class="mt-2 text-sm text-gray-700">
                                <?php if(($usage['is_used'] ?? false) && !empty($usage['where_used'] ?? [])): ?>
                                    <ul class="list-disc pl-5 space-y-1">
                                        <?php $__currentLoopData = $usage['where_used']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($u); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-gray-500">No usage detected yet.</div>
                                    <div class="text-xs text-gray-500 mt-2">
                                        (We scan Page body + SEO OG/Twitter image URLs. Builder integration will make this perfect later.)
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if(($usage['is_used'] ?? false)): ?>
                                <div class="mt-4 p-3 rounded bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                                    This file appears to be in use. Remove it from pages first, then delete.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/media/show.blade.php ENDPATH**/ ?>