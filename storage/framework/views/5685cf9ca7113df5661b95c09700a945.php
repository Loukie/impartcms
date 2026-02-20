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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <?php echo e($isCreate ? 'New header/footer block' : 'Edit header/footer block'); ?>

            </h2>

            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.layout-blocks.index')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Back
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <div class="font-semibold">Please fix the following:</div>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <form method="POST" action="<?php echo e($isCreate ? route('admin.layout-blocks.store') : route('admin.layout-blocks.update', $block)); ?>" class="p-6 space-y-8">
                    <?php echo csrf_field(); ?>
                    <?php if(!$isCreate): ?>
                        <?php echo method_field('PUT'); ?>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Content (HTML)</label>
                                <textarea name="content" rows="16" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm" placeholder="Paste HTML here… supports shortcodes like [icon …] and [form …]"><?php echo e(old('content', $block->content)); ?></textarea>
                                <p class="mt-1 text-xs text-gray-500">Rendered on the public site. Shortcodes are supported.</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Type</label>
                                        <select name="type" class="mt-1 w-full rounded-md border-gray-300">
                                            <option value="header" <?php echo e(old('type', $block->type) === 'header' ? 'selected' : ''); ?>>Header</option>
                                            <option value="footer" <?php echo e(old('type', $block->type) === 'footer' ? 'selected' : ''); ?>>Footer</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <input type="text" name="name" value="<?php echo e(old('name', $block->name)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Main navigation">
                                    </div>

                                    <div>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="is_enabled" value="1" <?php echo e(old('is_enabled', $block->is_enabled) ? 'checked' : ''); ?> class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                            Enabled
                                        </label>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Priority</label>
                                        <input type="number" name="priority" min="0" max="10000" value="<?php echo e(old('priority', $block->priority ?? 100)); ?>" class="mt-1 w-full rounded-md border-gray-300">
                                        <p class="mt-1 text-xs text-gray-500">Lower wins when multiple blocks match.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Targeting</label>
                                        <select name="target_mode" class="mt-1 w-full rounded-md border-gray-300">
                                            <option value="global" <?php echo e(old('target_mode', $block->target_mode) === 'global' ? 'selected' : ''); ?>>Global (all pages)</option>
                                            <option value="only" <?php echo e(old('target_mode', $block->target_mode) === 'only' ? 'selected' : ''); ?>>Only selected pages</option>
                                            <option value="except" <?php echo e(old('target_mode', $block->target_mode) === 'except' ? 'selected' : ''); ?>>All except selected pages</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Pages</label>
                                        <select name="page_ids[]" multiple size="10" class="mt-1 w-full rounded-md border-gray-300">
                                            <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $sel = collect(old('page_ids', $selectedPageIds ?? []))->map(fn($v) => (int)$v)->contains((int)$p->id);
                                                ?>
                                                <option value="<?php echo e($p->id); ?>" <?php echo e($sel ? 'selected' : ''); ?>>
                                                    <?php echo e($p->title); ?> (/<?php echo e(ltrim($p->slug,'/')); ?>)
                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">Used for “Only” / “Except”.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if(!$isCreate): ?>
                    <div class="px-6 pb-6">
                        <form method="POST" action="<?php echo e(route('admin.layout-blocks.destroy', $block)); ?>" onsubmit="return confirm('Move to trash?')" class="inline">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-700">
                                Trash
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/layout-blocks/edit.blade.php ENDPATH**/ ?>