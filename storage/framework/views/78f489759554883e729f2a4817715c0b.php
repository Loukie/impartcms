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
                <p class="text-sm text-gray-600 mt-1">Upload and manage files.</p>
            </div>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <?php
                        $baseTabQuery = request()->except('page', 'type');
                        $isAll = ($currentType ?? '') === '';
                        $isImages = ($currentType ?? '') === 'images';
                        $isDocs = ($currentType ?? '') === 'docs';
                        $isFonts = ($currentType ?? '') === 'fonts';
                    ?>

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-gray-600">
                            <a href="<?php echo e(route('admin.media.index', $baseTabQuery)); ?>"
                               class="<?php echo e($isAll ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                All <span class="text-gray-500">(<?php echo e($counts['all'] ?? 0); ?>)</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.media.index', array_merge($baseTabQuery, ['type' => 'images']))); ?>"
                               class="<?php echo e($isImages ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                Images <span class="text-gray-500">(<?php echo e($counts['images'] ?? 0); ?>)</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.media.index', array_merge($baseTabQuery, ['type' => 'fonts']))); ?>"
                               class="<?php echo e($isFonts ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                Fonts <span class="text-gray-500">(<?php echo e($counts['fonts'] ?? 0); ?>)</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.media.index', array_merge($baseTabQuery, ['type' => 'docs']))); ?>"
                               class="<?php echo e($isDocs ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                Docs <span class="text-gray-500">(<?php echo e($counts['docs'] ?? 0); ?>)</span>
                            </a>
                        </div>

                        <form method="GET" action="<?php echo e(route('admin.media.index')); ?>" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <input type="hidden" name="type" value="<?php echo e($currentType); ?>">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Folder</label>
                                <select name="folder" class="mt-1 rounded-md border-gray-300">
                                    <option value="" <?php echo e(($currentFolder ?? '') === '' ? 'selected' : ''); ?>>All folders</option>
                                    <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($f); ?>" <?php echo e(($currentFolder ?? '') === $f ? 'selected' : ''); ?>><?php echo e($f); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort</label>
                                <select name="sort" class="mt-1 rounded-md border-gray-300">
                                    <option value="newest" <?php echo e(($currentSort ?? '') === 'newest' ? 'selected' : ''); ?>>Newest</option>
                                    <option value="oldest" <?php echo e(($currentSort ?? '') === 'oldest' ? 'selected' : ''); ?>>Oldest</option>
                                    <option value="title_asc" <?php echo e(($currentSort ?? '') === 'title_asc' ? 'selected' : ''); ?>>Title A→Z</option>
                                    <option value="title_desc" <?php echo e(($currentSort ?? '') === 'title_desc' ? 'selected' : ''); ?>>Title Z→A</option>
                                    <option value="largest" <?php echo e(($currentSort ?? '') === 'largest' ? 'selected' : ''); ?>>Largest</option>
                                    <option value="smallest" <?php echo e(($currentSort ?? '') === 'smallest' ? 'selected' : ''); ?>>Smallest</option>
                                </select>
                            </div>

                            <div class="sm:ml-4">
                                <label class="block text-sm font-medium text-gray-700">Search</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="text" name="q" value="<?php echo e($currentQuery); ?>"
                                           placeholder="Search filename or title…"
                                           class="w-full sm:w-64 rounded-md border-gray-300" />

                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Apply
                                    </button>

                                    <a href="<?php echo e(route('admin.media.index')); ?>"
                                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6">
                        <form method="POST" action="<?php echo e(route('admin.media.store')); ?>" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="flex flex-col sm:flex-row gap-3 sm:items-end sm:justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Upload</label>
                                    <input name="files[]" type="file" multiple
                                           class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                                           accept="image/*,.svg,.ico,.pdf,.woff,.woff2,.ttf,.otf,.eot">
                                    <div class="text-xs text-gray-500 mt-1">Images, PDFs, and font files (max 10MB each). Auto-organised into YYYY/MM.</div>
                                </div>
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Upload
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php $__empty_1 = true; $__currentLoopData = $media; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <a href="<?php echo e(route('admin.media.show', $item)); ?>"
                               class="group border rounded-lg overflow-hidden hover:shadow-sm">
                                <div class="bg-gray-50 aspect-square flex items-center justify-center">
                                    <?php if($item->isImage()): ?>
                                        <img src="<?php echo e($item->url); ?>" alt="<?php echo e($item->alt_text ?? $item->original_name); ?>"
                                             class="h-full w-full object-cover group-hover:opacity-95">
                                    <?php else: ?>
                                        <div class="text-xs text-gray-600 px-2 text-center">
                                            <?php echo e(strtoupper(pathinfo($item->original_name, PATHINFO_EXTENSION) ?: 'FILE')); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-2">
                                    <div class="text-xs font-medium text-gray-900 truncate"><?php echo e($item->title ?: $item->original_name); ?></div>
                                    <div class="text-[11px] text-gray-500 truncate"><?php echo e($item->folder); ?></div>
                                </div>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="col-span-6 text-sm text-gray-500">
                                No media found.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6">
                        <?php echo e($media->links()); ?>

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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/media/index.blade.php ENDPATH**/ ?>