<form method="POST" action="<?php echo e(route('forms.submit', $form)); ?>" style="margin:16px 0; padding:16px; border:1px solid #ddd;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="_page_id" value="<?php echo e($page?->id); ?>">
    <input type="hidden" name="_override_to" value="<?php echo e($overrideTo); ?>">

    <h3 style="margin:0 0 12px 0;"><?php echo e($form->name); ?></h3>

    <?php $__currentLoopData = ($form->fields ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $name = $field['name'] ?? null;
            $label = $field['label'] ?? $name;
            $type = $field['type'] ?? 'text';
            $required = !empty($field['required']);
        ?>

        <?php if($name): ?>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:600;margin-bottom:6px;">
                    <?php echo e($label); ?> <?php if($required): ?> * <?php endif; ?>
                </label>

                <?php if($type === 'textarea'): ?>
                    <textarea name="<?php echo e($name); ?>" rows="4" style="width:100%;padding:8px;"><?php echo e(old($name)); ?></textarea>
                <?php else: ?>
                    <input type="<?php echo e($type === 'email' ? 'email' : 'text'); ?>" name="<?php echo e($name); ?>" value="<?php echo e(old($name)); ?>" style="width:100%;padding:8px;">
                <?php endif; ?>

                <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div style="color:#b00020;"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if(session('status')): ?>
        <div style="margin:10px 0; padding:10px; background:#e8fff1;"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <button type="submit" style="padding:10px 14px; background:#111; color:#fff; border:none;">Send</button>
</form>
<?php /**PATH C:\laragon\www\2kocms\resources\views/cms/forms/embed.blade.php ENDPATH**/ ?>