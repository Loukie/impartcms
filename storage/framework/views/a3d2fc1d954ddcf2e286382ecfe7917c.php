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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users</h2>
                <p class="text-sm text-gray-600 mt-1">Manage members and admin access.</p>
            </div>

            <div class="text-xs text-gray-600">
                Admins: <span class="font-semibold text-gray-900"><?php echo e($adminCount); ?></span>
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                        <?php echo e($user->name); ?>

                                        <?php if(auth()->id() === $user->id): ?>
                                            <span class="ml-2 text-xs px-2 py-0.5 rounded border border-gray-200 bg-gray-50 text-gray-700">You</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                        <?php echo e($user->email); ?>

                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <?php if($user->is_admin): ?>
                                            <span class="px-2 py-1 text-xs rounded border bg-indigo-50 text-indigo-800 border-indigo-200">Admin</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded border bg-gray-50 text-gray-700 border-gray-200">Member</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-gray-700 text-sm">
                                        <?php echo e(optional($user->created_at)->format('Y-m-d H:i')); ?>

                                    </td>

                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="<?php echo e(route('admin.users.edit', $user)); ?>"
                                               class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                Edit
                                            </a>

                                            <?php if(auth()->id() !== $user->id): ?>
                                                <form method="POST" action="<?php echo e(route('admin.users.toggleAdmin', $user)); ?>" class="inline"
                                                      onsubmit="return confirm('<?php echo e($user->is_admin ? 'Remove admin access for this user?' : 'Promote this user to admin?'); ?>');">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit"
                                                            class="text-gray-700 hover:text-gray-900 font-semibold text-sm">
                                                        <?php echo e($user->is_admin ? 'Remove Admin' : 'Make Admin'); ?>

                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-gray-400 font-semibold text-sm cursor-not-allowed"
                                                      title="You can’t change your own role here.">
                                                    <?php echo e($user->is_admin ? 'Admin' : 'Member'); ?>

                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                                        No users found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if(method_exists($users, 'links')): ?>
                        <div class="mt-6">
                            <?php echo e($users->links()); ?>

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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/users/index.blade.php ENDPATH**/ ?>