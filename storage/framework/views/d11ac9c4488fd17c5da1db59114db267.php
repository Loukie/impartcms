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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms settings</h2>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <ul class="list-disc list-inside text-sm">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($e); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="text-sm font-semibold text-gray-900">Delivery</div>
                        <div class="mt-1 text-xs text-gray-500">Like WP Mail SMTP, but scoped to Impart forms. Useful for local testing.</div>

                        <form method="POST" action="<?php echo e(route('admin.forms.settings.update')); ?>" class="mt-4 space-y-4">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600">Default recipient email(s)</label>
                                <input type="text" name="forms_default_to" value="<?php echo e(old('forms_default_to', $formsDefaultTo)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="you@example.com, admin@example.com">
                                <div class="mt-1 text-xs text-gray-500">Used when shortcode <code>to</code> is not set and the form itself has no default recipients.</div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600">Mail mode</label>
                                <select name="forms_mail_mode" class="mt-1 w-full rounded-md border-gray-300">
                                    <option value="inherit" <?php if(old('forms_mail_mode', $mailMode) === 'inherit'): echo 'selected'; endif; ?>>Inherit app mailer (MAIL_*)</option>
                                    <option value="smtp" <?php if(old('forms_mail_mode', $mailMode) === 'smtp'): echo 'selected'; endif; ?>>Use Forms SMTP settings (below)</option>
                                    <option value="log" <?php if(old('forms_mail_mode', $mailMode) === 'log'): echo 'selected'; endif; ?>>Log only (do not send)</option>
                                </select>
                                <div class="mt-1 text-xs text-gray-500">For local dev: choose <b>Log only</b> to confirm submissions store correctly without real email sending.</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">From name</label>
                                    <input type="text" name="forms_from_name" value="<?php echo e(old('forms_from_name', $fromName)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="ImpartCMS">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">From email</label>
                                    <input type="email" name="forms_from_email" value="<?php echo e(old('forms_from_email', $fromEmail)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="no-reply@yourdomain.com">
                                </div>
                            </div>

                            <hr>

                            <div class="text-sm font-semibold text-gray-900">Forms SMTP (optional)</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">SMTP host</label>
                                    <input type="text" name="forms_smtp_host" value="<?php echo e(old('forms_smtp_host', $smtpHost)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="smtp-relay.brevo.com">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Port</label>
                                    <input type="number" name="forms_smtp_port" value="<?php echo e(old('forms_smtp_port', $smtpPort)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="587">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Encryption</label>
                                    <select name="forms_smtp_encryption" class="mt-1 w-full rounded-md border-gray-300">
                                        <option value="tls" <?php if(old('forms_smtp_encryption', $smtpEncryption) === 'tls'): echo 'selected'; endif; ?>>TLS</option>
                                        <option value="ssl" <?php if(old('forms_smtp_encryption', $smtpEncryption) === 'ssl'): echo 'selected'; endif; ?>>SSL</option>
                                        <option value="none" <?php if(old('forms_smtp_encryption', $smtpEncryption) === ''): echo 'selected'; endif; ?>>None</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Username</label>
                                    <input type="text" name="forms_smtp_username" value="<?php echo e(old('forms_smtp_username', $smtpUsername)); ?>" class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600">Password <?php echo e($smtpPasswordSet ? '(already set)' : ''); ?></label>
                                    <input type="password" name="forms_smtp_password" value="" class="mt-1 w-full rounded-md border-gray-300" placeholder="Leave blank to keep current">
                                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="forms_smtp_password_clear" value="1" class="rounded border-gray-300">
                                        Clear stored password
                                    </label>
                                    <div class="mt-1 text-xs text-gray-500">Password is stored encrypted in the database.</div>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">Save settings</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="text-sm font-semibold text-gray-900">Send a test email</div>
                            <div class="mt-1 text-xs text-gray-500">Quick sanity-check (especially for local dev + Brevo).</div>
                            <form method="POST" action="<?php echo e(route('admin.forms.settings.testEmail')); ?>" class="mt-4 flex gap-2">
                                <?php echo csrf_field(); ?>
                                <input type="email" name="to" value="<?php echo e(old('to')); ?>" class="flex-1 rounded-md border-gray-300" placeholder="you@example.com" required>
                                <button class="px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">Send</button>
                            </form>
                            <div class="mt-2 text-xs text-gray-500">If Mail mode = Log, no email will be sent (that’s expected).</div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="p-6">
                            <div class="text-sm font-semibold text-gray-900">Recent delivery log</div>
                            <div class="mt-1 text-xs text-gray-500">Latest 25 submissions across all forms (status + recipient).</div>
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="text-xs uppercase text-gray-500 border-b">
                                        <tr>
                                            <th class="py-2 text-left">Date</th>
                                            <th class="py-2 text-left">Status</th>
                                            <th class="py-2 text-left">To</th>
                                            <th class="py-2 text-left">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td class="py-2 text-gray-700"><?php echo e(optional($s->created_at)->format('Y-m-d H:i')); ?></td>
                                                <td class="py-2 text-gray-900 font-semibold"><?php echo e(strtoupper($s->mail_status)); ?></td>
                                                <td class="py-2 text-gray-700"><?php echo e($s->to_email ?: '—'); ?></td>
                                                <td class="py-2 text-gray-700"><?php echo e($s->spam_reason ?: '—'); ?></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr><td class="py-4 text-gray-600" colspan="4">No submissions yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/forms/settings.blade.php ENDPATH**/ ?>