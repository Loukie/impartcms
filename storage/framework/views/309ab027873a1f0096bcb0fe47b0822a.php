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
                Pages
            </h2>

            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.pages.trash')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Trash
                </a>

                <a href="<?php echo e(route('admin.pages.create')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                    New Page
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
                <div class=\"mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200\">
                    <?php echo e($errors->first()); ?>

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
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__empty_1 = true; $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo e($page->title); ?>

                                            <?php if($page->is_homepage): ?>
                                                <span class="ml-2 text-xs px-2 py-0.5 rounded border border-gray-200 bg-gray-50 text-gray-700">
                                                    Home
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                            <?php echo e($page->slug); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded border
                                                <?php echo e($page->status === 'published'
                                                    ? 'bg-green-50 text-green-800 border-green-200'
                                                    : 'bg-yellow-50 text-yellow-800 border-yellow-200'); ?>">
                                                <?php echo e(strtoupper($page->status)); ?>

                                            </span>
                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            <?php echo e(optional($page->created_at)->format('Y-m-d H:i')); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                            <?php echo e(optional($page->updated_at)->format('Y-m-d H:i')); ?>

                                        </td>

                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-4">
                                                <?php if($page->status === 'published'): ?>
                                                    <a href="<?php echo e(url('/' . ltrim($page->slug, '/'))); ?>"
                                                       target="_blank"
                                                       class="underline text-sm text-gray-600 hover:text-gray-900">
                                                        View Live
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo e(route('pages.preview', $page)); ?>"
                                                       target="_blank"
                                                       class="underline text-sm text-gray-600 hover:text-gray-900">
                                                        Preview Draft
                                                    </a>
                                                <?php endif; ?>

                                                <a href="<?php echo e(route('admin.pages.edit', $page)); ?>"
                                                   class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                    Edit
                                                </a>

                                                <?php if($page->status === 'published' && !$page->is_homepage): ?>
                                                    <form method="POST" action="<?php echo e(route('admin.pages.setHomepage', $page)); ?>" class="inline"
                                                          onsubmit="return confirm('Set this page as the homepage (/)?');">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit"
                                                                class="text-gray-700 hover:text-gray-900 font-semibold text-sm">
                                                            Set Home
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if(!$page->is_homepage): ?>
                                                <form method="POST" action="<?php echo e(route('admin.pages.destroy', $page)); ?>"
                                                      onsubmit="return confirm('Move this page to trash?');"
                                                      class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                                        Trash
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                    <span class="text-gray-400 font-semibold text-sm cursor-not-allowed"
                                                          title="You can’t trash the homepage. Set a different homepage first.">
                                                        Trash
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                            No pages yet.
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
<?php endif; ?><?php /**PATH C:\laragon\www\2kocms\resources\views/admin/pages/index.blade.php ENDPATH**/ ?>