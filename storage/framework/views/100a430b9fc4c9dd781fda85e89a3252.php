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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media Details</h2>
                <p class="text-sm text-gray-600 mt-1">View usage, copy URLs, and edit metadata.</p>
            </div>

            <a href="<?php echo e(route('admin.media.index')); ?>"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                Back to Media
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
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
                <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="rounded-lg border bg-gray-50 overflow-hidden">
                            <div class="aspect-video flex items-center justify-center">
                                <?php if($media->isImage()): ?>
                                    <img src="<?php echo e($media->url); ?>" alt="<?php echo e($media->alt_text ?? $media->original_name); ?>" class="max-h-[420px] w-auto object-contain">
                                <?php else: ?>
                                    <div class="text-gray-600 text-sm"><?php echo e($media->original_name); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-sm font-semibold text-gray-900">Public URL</div>
                            <div class="mt-2 flex items-center gap-2">
                                <input id="media-url" readonly value="<?php echo e($media->url); ?>"
                                       class="w-full rounded-md border-gray-300 text-sm">
                                <button type="button" id="copy-url"
                                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Copy
                                </button>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">Tip: paste this URL into your page body or SEO image fields.</div>
                        </div>

                        <div class="mt-6 border-t pt-6">
                            <form method="POST" action="<?php echo e(route('admin.media.update', $media)); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PUT'); ?>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Title</label>
                                        <input name="title" type="text" value="<?php echo e(old('title', $media->title)); ?>"
                                               class="mt-1 w-full rounded-md border-gray-300">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Alt text</label>
                                        <input name="alt_text" type="text" value="<?php echo e(old('alt_text', $media->alt_text)); ?>"
                                               class="mt-1 w-full rounded-md border-gray-300">
                                        <div class="mt-1 text-xs text-gray-500">Used for accessibility + SEO (images only).</div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700">Caption</label>
                                    <textarea name="caption" rows="4" class="mt-1 w-full rounded-md border-gray-300"><?php echo e(old('caption', $media->caption)); ?></textarea>
                                </div>

                                <div class="mt-6 flex items-center justify-between">
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Save
                                    </button>

                                    <?php
                                        $usedCount = ($usage['pages']?->count() ?? 0) + ($usage['seo_pages']?->count() ?? 0);
                                    ?>

                                    <form method="POST" action="<?php echo e(route('admin.media.destroy', $media)); ?>"
                                          onsubmit="return confirm('Delete this media file? This cannot be undone.');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>

                                        <button type="submit"
                                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700 <?php echo e($usedCount > 0 ? 'opacity-50 cursor-not-allowed' : ''); ?>"
                                                <?php echo e($usedCount > 0 ? 'disabled' : ''); ?>>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-semibold text-gray-900">File info</div>
                        <dl class="mt-3 text-sm text-gray-700 space-y-2">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Name</dt>
                                <dd class="text-right truncate max-w-[16rem]" title="<?php echo e($media->original_name); ?>"><?php echo e($media->original_name); ?></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Folder</dt>
                                <dd class="text-right"><?php echo e($media->folder); ?></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Type</dt>
                                <dd class="text-right"><?php echo e($media->mime_type); ?></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Size</dt>
                                <dd class="text-right"><?php echo e(number_format(($media->size ?? 0) / 1024, 1)); ?> KB</dd>
                            </div>
                            <?php if($media->width && $media->height): ?>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Dimensions</dt>
                                    <dd class="text-right"><?php echo e($media->width); ?> × <?php echo e($media->height); ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>

                        <div class="mt-6 border-t pt-6">
                            <div class="text-sm font-semibold text-gray-900">Where used</div>

                            <?php
                                $pageCount = $usage['pages']?->count() ?? 0;
                                $seoCount = $usage['seo_pages']?->count() ?? 0;
                            ?>

                            <?php if(($pageCount + $seoCount) === 0): ?>
                                <div class="mt-2 text-sm text-gray-600">No usage detected yet.</div>
                                <div class="text-xs text-gray-500 mt-1">(We scan Page body + SEO OG/Twitter image URLs. Builder integration will make this perfect later.)</div>
                            <?php else: ?>
                                <div class="mt-2 text-xs text-gray-600">Detected usage (best-effort):</div>

                                <?php if($pageCount > 0): ?>
                                    <div class="mt-3">
                                        <div class="text-xs font-semibold text-gray-800">Pages (Body)</div>
                                        <ul class="mt-2 space-y-1">
                                            <?php $__currentLoopData = $usage['pages']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li class="text-sm">
                                                    <a class="underline text-gray-700 hover:text-gray-900" href="<?php echo e(route('admin.pages.edit', $p)); ?>">
                                                        <?php echo e($p->title); ?>

                                                    </a>
                                                    <span class="text-xs text-gray-500">(<?php echo e($p->status); ?>)</span>
                                                </li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if($seoCount > 0): ?>
                                    <div class="mt-4">
                                        <div class="text-xs font-semibold text-gray-800">Pages (SEO OG/Twitter)</div>
                                        <ul class="mt-2 space-y-1">
                                            <?php $__currentLoopData = $usage['seo_pages']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li class="text-sm">
                                                    <a class="underline text-gray-700 hover:text-gray-900" href="<?php echo e(route('admin.pages.edit', $p)); ?>">
                                                        <?php echo e($p->title); ?>

                                                    </a>
                                                    <span class="text-xs text-gray-500">(<?php echo e($p->status); ?>)</span>
                                                </li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-4 p-3 rounded bg-yellow-50 text-yellow-800 border border-yellow-200 text-sm">
                                    Delete is disabled while usage is detected.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const copyBtn = document.getElementById('copy-url');
            const input = document.getElementById('media-url');
            if (!copyBtn || !input) return;

            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(input.value || '');
                    copyBtn.textContent = 'Copied';
                    setTimeout(() => (copyBtn.textContent = 'Copy'), 1200);
                } catch (e) {
                    input.select();
                    document.execCommand('copy');
                    window.getSelection()?.removeAllRanges();
                }
            });
        })();
    </script>
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