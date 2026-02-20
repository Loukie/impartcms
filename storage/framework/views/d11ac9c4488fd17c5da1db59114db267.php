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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Forms settings</h2>

            <a href="<?php echo e(route('admin.forms.index')); ?>"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                Back
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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
                    <form method="POST" action="<?php echo e(route('admin.forms.settings.update')); ?>" class="space-y-6">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default recipients (CSV)</label>
                            <input type="text" name="default_to" value="<?php echo e(old('default_to', $defaults['default_to'] ?? '')); ?>" placeholder="you@example.com, team@example.com"
                                   class="mt-1 w-full rounded-md border-gray-300">
                            <div class="text-xs text-gray-500 mt-1">Used when shortcode does not specify <code>to=</code>.</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default CC (CSV, optional)</label>
                                <input type="text" name="default_cc" value="<?php echo e(old('default_cc', $defaults['default_cc'] ?? '')); ?>" placeholder="cc1@example.com, cc2@example.com"
                                       class="mt-1 w-full rounded-md border-gray-300">
                                <div class="text-xs text-gray-500 mt-1">Used when shortcode does not specify <code>cc=</code>.</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default BCC (CSV, optional)</label>
                                <input type="text" name="default_bcc" value="<?php echo e(old('default_bcc', $defaults['default_bcc'] ?? '')); ?>" placeholder="audit@example.com"
                                       class="mt-1 w-full rounded-md border-gray-300">
                                <div class="text-xs text-gray-500 mt-1">Used when shortcode does not specify <code>bcc=</code>.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">From name (optional)</label>
                                <input type="text" name="from_name" value="<?php echo e(old('from_name', $defaults['from_name'] ?? '')); ?>"
                                       class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">From email (optional)</label>
                                <input type="text" name="from_email" value="<?php echo e(old('from_email', $defaults['from_email'] ?? '')); ?>"
                                       class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reply-to email (optional)</label>
                            <input type="text" name="reply_to" value="<?php echo e(old('reply_to', $defaults['reply_to'] ?? '')); ?>"
                                   class="mt-1 w-full rounded-md border-gray-300">
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-gray-900">Email delivery</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Choose how form emails are sent. If you leave this on <strong>Use system mail config</strong>, Laravel will use your <code>.env</code> mail settings.
                            </p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Provider</label>
                                <select name="mail_provider" class="mt-1 w-full rounded-md border-gray-300">
                                    <?php $prov = old('mail_provider', $defaults['mail_provider'] ?? 'env'); ?>
                                    <option value="env" <?php echo e($prov === 'env' ? 'selected' : ''); ?>>Use system mail config (.env)</option>
                                    <option value="smtp" <?php echo e($prov === 'smtp' ? 'selected' : ''); ?>>Custom SMTP (override)</option>
                                    <option value="brevo" <?php echo e($prov === 'brevo' ? 'selected' : ''); ?>>Brevo API (Transactional Email)</option>
                                </select>
                            </div>

                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <div class="text-xs font-semibold text-gray-700">SMTP override (optional)</div>
                                    <div class="text-xs text-gray-500">Only used when Provider = Custom SMTP. Leave blank to keep existing secret values.</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP host</label>
                                    <input type="text" name="smtp_host" value="<?php echo e(old('smtp_host', $defaults['smtp_host'] ?? '')); ?>" placeholder="smtp.example.com"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP port</label>
                                    <input type="text" name="smtp_port" value="<?php echo e(old('smtp_port', $defaults['smtp_port'] ?? '587')); ?>" placeholder="587"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP username</label>
                                    <input type="text" name="smtp_username" value="<?php echo e(old('smtp_username', $defaults['smtp_username'] ?? '')); ?>"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">SMTP password</label>
                                    <input type="password" name="smtp_password" value="" placeholder="Leave blank to keep existing"
                                           class="mt-1 w-full rounded-md border-gray-300">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Encryption</label>
                                    <?php $enc = old('smtp_encryption', $defaults['smtp_encryption'] ?? 'tls'); ?>
                                    <select name="smtp_encryption" class="mt-1 w-full rounded-md border-gray-300">
                                        <option value="tls" <?php echo e($enc === 'tls' ? 'selected' : ''); ?>>TLS (recommended)</option>
                                        <option value="ssl" <?php echo e($enc === 'ssl' ? 'selected' : ''); ?>>SSL</option>
                                        <option value="starttls" <?php echo e($enc === 'starttls' ? 'selected' : ''); ?>>STARTTLS</option>
                                        <option value="none" <?php echo e($enc === '' || $enc === 'none' ? 'selected' : ''); ?>>None</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="text-xs font-semibold text-gray-700">Brevo API (optional)</div>
                                <div class="text-xs text-gray-500">Only used when Provider = Brevo API. We store this encrypted.</div>
                                <label class="block text-sm font-medium text-gray-700 mt-2">Brevo API key</label>
                                <input type="password" name="brevo_api_key" value="" placeholder="Leave blank to keep existing"
                                       class="mt-1 w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Save settings
                            </button>
                        </div>

                        <div class="text-xs text-gray-500 border-t pt-4 leading-relaxed">
                            Tip: Your form shortcode works like this:
                            <pre class="mt-2 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[form slug="contact"]
[form slug="contact" to="hello@example.com"]
[form slug="contact" to="hello@example.com" cc="sales@example.com" bcc="audit@example.com"]</code></pre>
                        </div>
                    </form>
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