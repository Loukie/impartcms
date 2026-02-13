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
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Media</h2>
            <p class="text-sm text-gray-600 mt-1">Upload and manage files.</p>
        </div>
     <?php $__env->endSlot(); ?>

    <?php
        $currentType = $currentType ?? 'images';
        $isImages = $currentType === 'images';
        $isIcons = $currentType === 'icons';
        $isDocs = $currentType === 'docs';

        $baseTabQuery = [
            'folder' => $currentFolder ?? '',
            'q' => $currentQuery ?? '',
            'sort' => $currentSort ?? 'newest',
        ];
    ?>

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
                <div class="p-6" x-data="{ showUpload: <?php echo e($errors->any() ? 'true' : 'false'); ?> }">

                    
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <a href="<?php echo e(route('admin.media.index', array_merge($baseTabQuery, ['type' => 'images']))); ?>"
                               class="<?php echo e($isImages ? 'text-gray-900' : 'text-gray-500 hover:text-gray-900'); ?>">
                                Images <span class="text-gray-400">(<?php echo e($counts['images'] ?? 0); ?>)</span>
                            </a>
                            <span class="text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.media.index', array_merge($baseTabQuery, ['type' => 'icons']))); ?>"
                               class="<?php echo e($isIcons ? 'text-gray-900' : 'text-gray-500 hover:text-gray-900'); ?>">
                                Icons
                            </a>
                            <span class="text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.media.index', array_merge($baseTabQuery, ['type' => 'docs']))); ?>"
                               class="<?php echo e($isDocs ? 'text-gray-900' : 'text-gray-500 hover:text-gray-900'); ?>">
                                Docs <span class="text-gray-400">(<?php echo e($counts['docs'] ?? 0); ?>)</span>
                            </a>
                        </div>

                        
                        <div class="flex items-center gap-2 flex-wrap">
                            <?php if(!$isIcons): ?>
                                <form method="GET" action="<?php echo e(route('admin.media.index')); ?>" class="flex items-center gap-2 flex-wrap">
                                    <input type="hidden" name="type" value="<?php echo e($currentType); ?>">

                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-600">Folder</label>
                                        <select name="folder" class="border rounded-md text-sm px-3 py-2">
                                            <option value="">All folders</option>
                                            <?php $__currentLoopData = ($folders ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($folder); ?>" <?php if(($currentFolder ?? '') === $folder): echo 'selected'; endif; ?>><?php echo e($folder); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-600">Sort</label>
                                        <select name="sort" class="border rounded-md text-sm px-3 py-2">
                                            <option value="newest" <?php if(($currentSort ?? 'newest') === 'newest'): echo 'selected'; endif; ?>>Newest</option>
                                            <option value="oldest" <?php if(($currentSort ?? '') === 'oldest'): echo 'selected'; endif; ?>>Oldest</option>
                                            <option value="title_asc" <?php if(($currentSort ?? '') === 'title_asc'): echo 'selected'; endif; ?>>Title (A→Z)</option>
                                            <option value="title_desc" <?php if(($currentSort ?? '') === 'title_desc'): echo 'selected'; endif; ?>>Title (Z→A)</option>
                                        </select>
                                    </div>

                                    <input
                                        type="text"
                                        name="q"
                                        value="<?php echo e($currentQuery ?? ''); ?>"
                                        class="border rounded-md text-sm px-3 py-2"
                                        placeholder="Search filename or title..."
                                    />

                                    <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold">Apply</button>
                                    <a href="<?php echo e(route('admin.media.index', ['type' => $currentType])); ?>" class="px-3 py-2 rounded-md border text-xs font-semibold">Reset</a>
                                </form>

                                <button type="button"
                                        class="ml-auto px-3 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold"
                                        @click="showUpload = !showUpload">
                                    Upload
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    
                    <?php if(!$isIcons): ?>
                        <div class="mt-4" x-show="showUpload" x-cloak>
                            <form method="POST" action="<?php echo e(route('admin.media.store')); ?>" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <?php echo csrf_field(); ?>
                                <input type="file" name="files[]" multiple class="block w-full text-sm" />
                                <button type="submit" class="px-4 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold">Upload</button>
                            </form>
                            <div class="mt-2 text-xs text-gray-500">
                                Images and documents (max 10MB each). Auto-organised into YYYY/MM.
                            </div>
                        </div>
                    <?php endif; ?>

                    
                    <div class="mt-6">
                        <?php if($isIcons): ?>
                            
                            <?php echo $__env->make('admin.media.partials.fa-icons', ['mode' => 'copy'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php else: ?>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                <?php $__empty_1 = true; $__currentLoopData = $media; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <a href="<?php echo e(route('admin.media.show', $item)); ?>" class="group block border rounded-lg overflow-hidden bg-white hover:shadow">
                                        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
                                            <?php if($item->is_image): ?>
                                                <img src="<?php echo e($item->url); ?>" alt="<?php echo e($item->title ?? $item->original_name ?? ''); ?>" class="w-full h-full object-contain p-2" />
                                            <?php else: ?>
                                                <div class="text-xs font-semibold text-gray-600"><?php echo e(strtoupper($item->extension ?? 'FILE')); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-2">
                                            <div class="text-sm font-semibold text-gray-900 truncate group-hover:text-gray-900">
                                                <?php echo e($item->title ?: ($item->original_name ?? 'Untitled')); ?>

                                            </div>
                                            <div class="text-[11px] text-gray-500 truncate">
                                                <?php echo e($item->folder ?? ''); ?>

                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="text-sm text-gray-500 col-span-full">No media found.</div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-6">
                                <?php echo e($media->links()); ?>

                            </div>
                        <?php endif; ?>
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