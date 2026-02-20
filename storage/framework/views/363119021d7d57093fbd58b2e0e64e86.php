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
                <?php echo e($isNew ? 'New snippet' : 'Edit snippet'); ?>

            </h2>

            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.snippets.index')); ?>"
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST"
                      action="<?php echo e($isNew ? route('admin.snippets.store') : route('admin.snippets.update', $snippet)); ?>"
                      class="p-6 space-y-8">
                    <?php echo csrf_field(); ?>
                    <?php if(!$isNew): ?>
                        <?php echo method_field('PUT'); ?>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="type" id="snippet_type" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="script" <?php echo e(old('type', $snippet->type) === 'script' ? 'selected' : ''); ?>>Script</option>
                                <option value="css" <?php echo e(old('type', $snippet->type) === 'css' ? 'selected' : ''); ?>>CSS</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Scripts can be placed in Head / Body / Footer. CSS always loads at the end of &lt;head&gt; so it overrides theme styles.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" value="<?php echo e(old('name', $snippet->name)); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g. Google Analytics" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_enabled" value="1"
                                       <?php echo e(old('is_enabled', $snippet->is_enabled) ? 'checked' : ''); ?>

                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                Enabled
                            </label>
                        </div>

                        <div id="position_wrap">
                            <label class="block text-sm font-medium text-gray-700">Script placement</label>
                            <select name="position" id="snippet_position" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="head" <?php echo e(old('position', $snippet->position) === 'head' ? 'selected' : ''); ?>>Head</option>
                                <option value="body" <?php echo e(old('position', $snippet->position) === 'body' ? 'selected' : ''); ?>>Body (top)</option>
                                <option value="footer" <?php echo e(old('position', $snippet->position) === 'footer' ? 'selected' : ''); ?>>Footer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Targeting</label>
                            <select name="target_mode" id="snippet_target_mode" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="global" <?php echo e(old('target_mode', $snippet->target_mode) === 'global' ? 'selected' : ''); ?>>Global (all pages)</option>
                                <option value="only" <?php echo e(old('target_mode', $snippet->target_mode) === 'only' ? 'selected' : ''); ?>>Only selected pages</option>
                                <option value="except" <?php echo e(old('target_mode', $snippet->target_mode) === 'except' ? 'selected' : ''); ?>>All except selected pages</option>
                            </select>
                        </div>
                    </div>

                    <div id="pages_wrap">
                        <label class="block text-sm font-medium text-gray-700">Target pages</label>
                        <select name="page_ids[]" multiple class="mt-1 block w-full rounded-md border-gray-300 min-h-[160px]">
                            <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $selected = in_array((int) $p->id, (array) old('page_ids', $selectedPageIds ?? []), true);
                                ?>
                                <option value="<?php echo e($p->id); ?>" <?php echo e($selected ? 'selected' : ''); ?>><?php echo e($p->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Used when Targeting is “Only selected pages” or “All except selected pages”.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Code</label>
                        <textarea name="content" rows="16"
                                  class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm"
                                  placeholder="Paste your code here… (You can paste full <script> tags or raw JS)"><?php echo e(old('content', $snippet->content)); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">This applies to the public site (front end). Admin pages are not affected.</p>
                    </div>

                    <div class="pt-6 border-t flex items-center justify-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const typeEl = document.getElementById('snippet_type');
            const positionWrap = document.getElementById('position_wrap');
            const pagesWrap = document.getElementById('pages_wrap');
            const targetModeEl = document.getElementById('snippet_target_mode');

            function refresh() {
                const type = (typeEl?.value || 'script');
                if (positionWrap) {
                    positionWrap.style.display = (type === 'script') ? '' : 'none';
                }

                const mode = (targetModeEl?.value || 'global');
                if (pagesWrap) {
                    pagesWrap.style.display = (mode === 'global') ? 'none' : '';
                }
            }

            typeEl?.addEventListener('change', refresh);
            targetModeEl?.addEventListener('change', refresh);
            refresh();
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/snippets/edit.blade.php ENDPATH**/ ?>