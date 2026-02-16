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
                <a href="<?php echo e(route('admin.forms.submissions.index', $form)); ?>" class="text-sm font-semibold text-gray-700 hover:text-gray-900">← Submissions</a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submission #<?php echo e($submission->id); ?></h2>
                    <div class="text-xs text-gray-500"><?php echo e($form->name); ?> (<?php echo e($form->slug); ?>)</div>
                </div>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">Created</div>
                            <div class="mt-1 text-gray-900"><?php echo e(optional($submission->created_at)->toDayDateTimeString()); ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">Status</div>
                            <div class="mt-1 text-gray-900"><?php echo e(strtoupper($submission->mail_status)); ?></div>
                            <?php if($submission->mail_sent_at): ?>
                                <div class="mt-1 text-sm text-gray-600">Sent: <?php echo e($submission->mail_sent_at->toDayDateTimeString()); ?></div>
                            <?php endif; ?>
                            <?php if($submission->spam_reason): ?>
                                <div class="mt-1 text-sm text-gray-600">Reason: <?php echo e($submission->spam_reason); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">To</div>
                            <div class="mt-1 text-gray-900"><?php echo e($submission->to_email ?: '—'); ?></div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase">IP / UA</div>
                            <div class="mt-1 text-gray-900"><?php echo e($submission->ip ?: '—'); ?></div>
                            <div class="mt-1 text-xs text-gray-600 break-all"><?php echo e($submission->user_agent ?: '—'); ?></div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Payload</div>
                        <pre class="mt-2 rounded-lg bg-gray-900 text-gray-100 p-4 text-xs overflow-x-auto"><?php echo e(json_encode($submission->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                    </div>

                    <?php if($submission->mail_error): ?>
                        <div>
                            <div class="text-xs font-semibold text-rose-700 uppercase">Mail error</div>
                            <pre class="mt-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-900 p-4 text-xs overflow-x-auto"><?php echo e($submission->mail_error); ?></pre>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/forms/submissions/show.blade.php ENDPATH**/ ?>