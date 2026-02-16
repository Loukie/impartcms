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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms</h2>

            <div class="flex items-center gap-2">
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

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-xs uppercase text-gray-500 border-b">
                                <tr>
                                    <th class="py-3 text-left">Name</th>
                                    <th class="py-3 text-left">Slug</th>
                                    <th class="py-3 text-left">Active</th>
                                    <th class="py-3 text-left">Shortcode</th>
                                    <th class="py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__empty_1 = true; $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 font-semibold text-gray-900"><?php echo e($form->name); ?></td>
                                        <td class="py-3 text-gray-700"><?php echo e($form->slug); ?></td>
                                        <td class="py-3">
                                            <?php if($form->is_active): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-green-50 text-green-800 border border-green-200 text-xs font-semibold">Yes</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-50 text-gray-700 border border-gray-200 text-xs font-semibold">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-gray-700">
                                            <code class="px-2 py-1 rounded bg-gray-100">[form slug="<?php echo e($form->slug); ?>"]</code>
                                        </td>
                                        <td class="py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="<?php echo e(route('admin.forms.submissions.index', $form)); ?>"
                                                   class="px-3 py-1.5 rounded border border-gray-300 text-xs font-semibold hover:bg-gray-50">Submissions</a>
                                                <a href="<?php echo e(route('admin.forms.edit', $form)); ?>"
                                                   class="px-3 py-1.5 rounded bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td class="py-6 text-gray-600" colspan="5">No forms yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4"><?php echo e($forms->links()); ?></div>
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