<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select media</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-slate-50">
<div class="p-4">
    <?php
        $currentType = $currentType ?? 'images';
        $tab = $tab ?? 'library';

        // Allow can restrict which tabs show up (CSV): e.g. allow=images,icons (hide docs)
        $allowRaw = (string) request()->query('allow', '');
        $allowedTabs = [];
        if (trim($allowRaw) !== '') {
            $allowedTabs = array_values(array_filter(array_map(function ($v) {
                return strtolower(trim((string) $v));
            }, explode(',', $allowRaw)), fn ($v) => in_array($v, ['images', 'icons', 'docs'], true)));
        }
        if (empty($allowedTabs)) {
            $allowedTabs = ['images', 'icons', 'docs'];
        }

        // Normalise active tab
        if (!in_array($currentType, $allowedTabs, true)) {
            $currentType = $allowedTabs[0] ?? 'images';
        }

        $isIcons = $currentType === 'icons';
        $hideUpload = !in_array('images', $allowedTabs, true) && !in_array('docs', $allowedTabs, true);
    ?>

    
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.media.picker', array_merge(request()->query(), ['tab' => 'library']))); ?>"
               class="px-3 py-2 rounded-md text-sm font-semibold border <?php echo e(($tab ?? 'library') === 'library' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-900'); ?>">
                Library
            </a>

            <?php if(!$hideUpload): ?>
                <a href="<?php echo e(route('admin.media.picker', array_merge(request()->query(), ['tab' => 'upload']))); ?>"
                   class="px-3 py-2 rounded-md text-sm font-semibold border <?php echo e(($tab ?? 'library') === 'upload' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-900'); ?>">
                    Upload
                </a>
            <?php endif; ?>

            <button type="button"
                    class="px-3 py-2 rounded-md text-sm font-semibold border bg-white text-gray-900 hover:bg-gray-50"
                    onclick="window.parent?.ImpartMediaPicker?.close?.()">
                Cancel
            </button>
        </div>

        <?php if(!$isIcons): ?>
            <form method="GET" class="flex items-center gap-2 flex-wrap">
                <input type="hidden" name="tab" value="<?php echo e($tab ?? 'library'); ?>">
                <input type="hidden" name="type" value="<?php echo e($currentType ?? ''); ?>">
                <input type="hidden" name="sort" value="<?php echo e($currentSort ?? 'newest'); ?>">

                <select name="folder" class="rounded-md border-gray-300 text-sm">
                    <option value="">All folders</option>
                    <?php $__currentLoopData = ($folders ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($f); ?>" <?php if(($currentFolder ?? '') === $f): echo 'selected'; endif; ?>><?php echo e($f); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>

                <input name="q" value="<?php echo e($currentQuery ?? ''); ?>" placeholder="Search…"
                       class="rounded-md border-gray-300 text-sm w-64 max-w-full"/>

                <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                    Apply
                </button>

                <a href="<?php echo e(route('admin.media.picker', ['type' => $currentType, 'tab' => $tab])); ?>"
                   class="px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50">
                    Reset
                </a>
            </form>
        <?php endif; ?>
    </div>

    
    <div class="mt-4 border-b border-slate-200 flex items-center gap-4 text-sm font-semibold">
        <?php
            $tabs = [
                'images' => ['Images', $counts['images'] ?? 0],
                'icons' => ['Icons', null],
                'docs' => ['Docs', $counts['docs'] ?? 0],
            ];
            $active = $currentType ?? 'images';
        ?>

        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $count]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!in_array($key, $allowedTabs, true)) continue; ?>
            <a href="<?php echo e(route('admin.media.picker', array_merge(request()->query(), ['type' => $key]))); ?>"
               class="px-2 py-2 -mb-px border-b-2 <?php echo e($active === $key ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-800'); ?>">
                <?php echo e($label); ?>

                <?php if(!is_null($count)): ?>
                    <span class="text-gray-400">(<?php echo e($count); ?>)</span>
                <?php endif; ?>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if(($tab ?? 'library') === 'upload' && !$hideUpload): ?>
        <div class="mt-4 bg-white border rounded-xl p-4">
            <form method="POST" action="<?php echo e(route('admin.media.store')); ?>" enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
                <?php echo csrf_field(); ?>
                <input type="file" name="files[]" multiple class="text-sm">
                <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                    Upload
                </button>
                <div class="text-xs text-gray-500">
                    Images + Docs (PDFs + fonts) (max 10MB each)
                </div>
            </form>

            <?php if($errors->any()): ?>
                <div class="mt-3 text-sm text-red-600">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    
    <div class="mt-4">
        <?php if($isIcons): ?>
            <?php echo $__env->make('admin.media.partials.fa-icons', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <?php $__currentLoopData = $media; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $url = $m->url ?? asset('storage/' . ltrim($m->path, '/'));
                        $isImage = method_exists($m, 'isImage') ? $m->isImage() : (str_starts_with((string) $m->mime_type, 'image/'));
                    ?>

                    <div class="bg-white border rounded-xl overflow-hidden hover:shadow-sm transition">
                        <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden">
                            <?php if($isImage): ?>
                                <img src="<?php echo e($url); ?>" alt="" class="w-full h-full object-contain">
                            <?php else: ?>
                                <div class="text-xs text-slate-500 p-3 text-center break-all">
                                    <?php echo e($m->original_name ?? $m->filename); ?>

                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-slate-900 truncate"><?php echo e($m->title ?? 'Untitled'); ?></div>
                                <div class="text-[11px] text-slate-500 truncate"><?php echo e($m->folder ?? ''); ?></div>
                            </div>

                            <button type="button"
                                class="px-2 py-1 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800"
                                onclick="selectMedia(<?php echo \Illuminate\Support\Js::from([
                                    'id' => $m->id,
                                    'url' => $url,
                                    'title' => $m->title,
                                    'mime_type' => $m->mime_type,
                                ])->toHtml() ?>)">
                                Select
                            </button>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="mt-4">
                <?php echo e($media->links()); ?>

            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function selectMedia(payload) {
    window.parent.postMessage(
        { type: 'impart-media-selected', payload },
        window.location.origin
    );
}
</script>
</body>
</html>
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/media/picker.blade.php ENDPATH**/ ?>