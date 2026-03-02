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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms</h2>
                <p class="text-sm text-gray-600 mt-1">Manage forms and submissions.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.forms.trash')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>

                <a href="<?php echo e(route('admin.forms.settings.edit')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Settings
                </a>

                <a href="<?php echo e(route('admin.forms.create')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    New Form
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
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <?php
                        $baseTabQuery = request()->except('page', 'status');
                        $isAll = ($currentStatus ?? '') === '';
                        $isActive = ($currentStatus ?? '') === 'active';
                        $isInactive = ($currentStatus ?? '') === 'inactive';
                    ?>

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-gray-600">
                            <a href="<?php echo e(route('admin.forms.index', $baseTabQuery)); ?>"
                               class="<?php echo e($isAll ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                All <span class="text-gray-500">(<?php echo e($counts['all'] ?? 0); ?>)</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.forms.index', array_merge($baseTabQuery, ['status' => 'active']))); ?>"
                               class="<?php echo e($isActive ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                Active <span class="text-gray-500">(<?php echo e($counts['active'] ?? 0); ?>)</span>
                            </a>
                            <span class="mx-2 text-gray-300">|</span>
                            <a href="<?php echo e(route('admin.forms.index', array_merge($baseTabQuery, ['status' => 'inactive']))); ?>"
                               class="<?php echo e($isInactive ? 'font-semibold text-gray-900' : 'hover:text-gray-900'); ?>">
                                Inactive <span class="text-gray-500">(<?php echo e($counts['inactive'] ?? 0); ?>)</span>
                            </a>
                        </div>

                        <form method="GET" action="<?php echo e(route('admin.forms.index')); ?>" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                            <input type="hidden" name="status" value="<?php echo e($currentStatus); ?>">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort</label>
                                <select name="sort" class="mt-1 rounded-md border-gray-300">
                                    <option value="updated_desc" <?php echo e(($currentSort ?? '') === 'updated_desc' ? 'selected' : ''); ?>>Recently updated</option>
                                    <option value="updated_asc" <?php echo e(($currentSort ?? '') === 'updated_asc' ? 'selected' : ''); ?>>Least recently updated</option>
                                    <option value="created_desc" <?php echo e(($currentSort ?? '') === 'created_desc' ? 'selected' : ''); ?>>Newest</option>
                                    <option value="created_asc" <?php echo e(($currentSort ?? '') === 'created_asc' ? 'selected' : ''); ?>>Oldest</option>
                                    <option value="name_asc" <?php echo e(($currentSort ?? '') === 'name_asc' ? 'selected' : ''); ?>>Name A→Z</option>
                                    <option value="name_desc" <?php echo e(($currentSort ?? '') === 'name_desc' ? 'selected' : ''); ?>>Name Z→A</option>
                                </select>
                            </div>

                            <div class="sm:ml-4">
                                <label class="block text-sm font-medium text-gray-700">Search</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="text" name="q" value="<?php echo e($currentQuery); ?>"
                                           placeholder="Search name or slug…"
                                           class="w-full sm:w-64 rounded-md border-gray-300" />

                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                        Apply
                                    </button>

                                    <a href="<?php echo e(route('admin.forms.index')); ?>"
                                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submissions</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__empty_1 = true; $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo e($form->name); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                            <?php echo e($form->slug); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded border <?php echo e($form->is_active ? 'bg-green-50 text-green-800 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200'); ?>">
                                                <?php echo e($form->is_active ? 'Yes' : 'No'); ?>

                                            </span>
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            <?php echo e((int) ($form->submissions_count ?? 0)); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            <?php echo e(optional($form->updated_at)->format('Y-m-d H:i')); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="<?php echo e(route('admin.forms.submissions.index', $form)); ?>"
                                                   class="underline text-sm text-gray-600 hover:text-gray-900">
                                                    View submissions
                                                </a>

                                                <a href="<?php echo e(route('admin.forms.edit', $form)); ?>"
                                                   class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                    Edit
                                                </a>

                                                <form method="POST" action="<?php echo e(route('admin.forms.destroy', $form)); ?>"
                                                      onsubmit="return confirm('Move this form to trash?');"
                                                      class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                                        Trash
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                            No forms found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if(method_exists($forms, 'links')): ?>
                        <div class="mt-6">
                            <?php echo e($forms->links()); ?>

                        </div>
                    <?php endif; ?>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/forms/index.blade.php ENDPATH**/ ?>