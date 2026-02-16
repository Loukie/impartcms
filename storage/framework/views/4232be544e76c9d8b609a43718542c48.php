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
            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.forms.index')); ?>" class="text-sm font-semibold text-gray-700 hover:text-gray-900">← Forms</a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submissions</h2>
                    <div class="text-xs text-gray-500"><?php echo e($form->name); ?> (<?php echo e($form->slug); ?>)</div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.forms.edit', $form)); ?>" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">Edit form</a>
                <a href="<?php echo e(route('admin.forms.submissions.export', $form, ['status' => $status])); ?>" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">Export CSV</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <?php
                            $pill = 'inline-flex items-center px-3 py-1.5 rounded-full border text-xs font-semibold';
                            $active = 'bg-gray-900 border-gray-900 text-white';
                            $inactive = 'bg-white border-gray-300 text-gray-800 hover:bg-gray-50';
                            $makeUrl = fn($s) => route('admin.forms.submissions.index', [$form, 'status' => $s]);
                        ?>

                        <a href="<?php echo e($makeUrl('')); ?>" class="<?php echo e($pill); ?> <?php echo e($status==='' ? $active : $inactive); ?>">All (<?php echo e(array_sum($stats)); ?>)</a>
                        <a href="<?php echo e($makeUrl('sent')); ?>" class="<?php echo e($pill); ?> <?php echo e($status==='sent' ? $active : $inactive); ?>">Sent (<?php echo e($stats['sent'] ?? 0); ?>)</a>
                        <a href="<?php echo e($makeUrl('failed')); ?>" class="<?php echo e($pill); ?> <?php echo e($status==='failed' ? $active : $inactive); ?>">Failed (<?php echo e($stats['failed'] ?? 0); ?>)</a>
                        <a href="<?php echo e($makeUrl('skipped')); ?>" class="<?php echo e($pill); ?> <?php echo e($status==='skipped' ? $active : $inactive); ?>">Skipped (<?php echo e($stats['skipped'] ?? 0); ?>)</a>
                        <a href="<?php echo e($makeUrl('pending')); ?>" class="<?php echo e($pill); ?> <?php echo e($status==='pending' ? $active : $inactive); ?>">Pending (<?php echo e($stats['pending'] ?? 0); ?>)</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-xs uppercase text-gray-500 border-b">
                                <tr>
                                    <th class="py-3 text-left">Date</th>
                                    <th class="py-3 text-left">Status</th>
                                    <th class="py-3 text-left">To</th>
                                    <th class="py-3 text-left">IP</th>
                                    <th class="py-3 text-right">View</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php $__empty_1 = true; $__currentLoopData = $submissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 text-gray-800"><?php echo e(optional($s->created_at)->format('Y-m-d H:i')); ?></td>
                                        <td class="py-3">
                                            <?php
                                                $map = [
                                                    'sent' => 'bg-green-50 text-green-800 border-green-200',
                                                    'failed' => 'bg-rose-50 text-rose-800 border-rose-200',
                                                    'skipped' => 'bg-gray-50 text-gray-700 border-gray-200',
                                                    'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                ];
                                                $cls = $map[$s->mail_status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded border text-xs font-semibold <?php echo e($cls); ?>">
                                                <?php echo e(strtoupper($s->mail_status)); ?>

                                            </span>
                                            <?php if($s->spam_reason): ?>
                                                <div class="mt-1 text-xs text-gray-500"><?php echo e($s->spam_reason); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-gray-700"><?php echo e($s->to_email ?: '—'); ?></td>
                                        <td class="py-3 text-gray-700"><?php echo e($s->ip ?: '—'); ?></td>
                                        <td class="py-3 text-right">
                                            <a href="<?php echo e(route('admin.forms.submissions.show', [$form, $s])); ?>"
                                               class="px-3 py-1.5 rounded border border-gray-300 text-xs font-semibold hover:bg-gray-50">Open</a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td class="py-6 text-gray-600" colspan="5">No submissions yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4"><?php echo e($submissions->links()); ?></div>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/forms/submissions/index.blade.php ENDPATH**/ ?>