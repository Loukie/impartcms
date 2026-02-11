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
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Trash
                </h2>

                <a href="<?php echo e(route('admin.pages.index')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    Back to Pages
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__empty_1 = true; $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo e($page->title); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                            <?php echo e($page->slug); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            <?php echo e(optional($page->created_at)->format('Y-m-d H:i')); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            <?php echo e(optional($page->updated_at)->format('Y-m-d H:i')); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                            <?php echo e(optional($page->deleted_at)->format('Y-m-d H:i')); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="<?php echo e(route('pages.preview', $page->id)); ?>"
                                                   target="_blank"
                                                   class="underline text-sm text-gray-600 hover:text-gray-900">
                                                    Preview
                                                </a>

                                                <form method="POST" action="<?php echo e(route('admin.pages.restore', ['pageTrash' => $page->id])); ?>"
                                                      onsubmit="return confirm('Restore this page?');"
                                                      class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit"
                                                            class="text-green-700 hover:text-green-900 font-semibold text-sm">
                                                        Restore
                                                    </button>
                                                </form>

                                                <form method="POST" action="<?php echo e(route('admin.pages.forceDestroy', ['pageTrash' => $page->id])); ?>"
                                                      onsubmit="return confirm('Delete permanently? This cannot be undone.');"
                                                      class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                                        Delete Permanently
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                            Trash is empty.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if(method_exists($pages, 'links')): ?>
                        <div class="mt-6">
                            <?php echo e($pages->links()); ?>

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
<?php endif; ?><?php /**PATH C:\laragon\www\2kocms\resources\views/admin/pages/trash.blade.php ENDPATH**/ ?>