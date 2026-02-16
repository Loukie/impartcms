<?php
    /**
     * Public embed renderer for a Form.
     * - Uses schema stored on $form->fields (array)
     * - Includes honeypot + basic helpers (phone country dropdown)
     */
    $fields = is_array($form->fields ?? null) ? $form->fields : [];
?>

<div class="my-6">
    <form method="POST" action="<?php echo e(route('forms.submit', $form)); ?>" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="_page_id" value="<?php echo e($page?->id); ?>">
        <input type="hidden" name="_override_to" value="<?php echo e($overrideTo); ?>">

        
        <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label>Leave this field empty</label>
            <input type="text" name="__hp" tabindex="-1" autocomplete="off">
        </div>

        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900"><?php echo e($form->name); ?></h3>
                <?php if(!empty($form->settings['description'])): ?>
                    <p class="mt-1 text-sm text-slate-600"><?php echo e($form->settings['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if(session('status')): ?>
            <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <div class="mt-5 space-y-4">
            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $name = $field['name'] ?? null;
                    $label = $field['label'] ?? $name;
                    $type = $field['type'] ?? 'text';
                    $required = !empty($field['required']);
                    $placeholder = $field['placeholder'] ?? '';
                    $help = $field['help'] ?? '';
                    $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                ?>

                <?php if($type === 'heading'): ?>
                    <div class="pt-2">
                        <div class="text-base font-semibold text-slate-900"><?php echo e($label); ?></div>
                        <?php if($help): ?>
                            <div class="mt-1 text-sm text-slate-600"><?php echo e($help); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php continue; ?>
                <?php endif; ?>

                <?php if($type === 'html'): ?>
                    <div class="prose prose-slate max-w-none"><?php echo $field['html'] ?? ''; ?></div>
                    <?php continue; ?>
                <?php endif; ?>

                <?php if(!$name): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-slate-900">
                        <?php echo e($label); ?>

                        <?php if($required): ?>
                            <span class="text-rose-600">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if($help): ?>
                        <div class="mt-1 text-xs text-slate-500"><?php echo e($help); ?></div>
                    <?php endif; ?>

                    <?php if($type === 'textarea'): ?>
                        <textarea
                            name="<?php echo e($name); ?>"
                            rows="4"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                            placeholder="<?php echo e($placeholder); ?>"
                            <?php if($required): ?> required <?php endif; ?>
                        ><?php echo e(old($name)); ?></textarea>
                    <?php elseif($type === 'select'): ?>
                        <select
                            name="<?php echo e($name); ?>"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                            <?php if($required): ?> required <?php endif; ?>
                        >
                            <option value="">Select…</option>
                            <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? '') : (string) $opt;
                                    $ol = is_array($opt) ? ($opt['label'] ?? $ov) : (string) $opt;
                                ?>
                                <option value="<?php echo e($ov); ?>" <?php if(old($name) == $ov): echo 'selected'; endif; ?>><?php echo e($ol); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php elseif($type === 'cards' || $type === 'cards_multi'): ?>
                        <?php
                            $isMulti = $type === 'cards_multi';
                            $oldVal = old($name);
                            $oldArr = is_array($oldVal) ? $oldVal : (is_string($oldVal) ? [$oldVal] : []);
                        ?>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $ov = is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? ('option_' . $idx)) : (string) $opt;
                                    $ol = is_array($opt) ? ($opt['label'] ?? $ov) : (string) $opt;
                                    $mediaId = is_array($opt) ? (int) ($opt['media_id'] ?? 0) : 0;
                                    $mediaUrl = null;
                                    if ($mediaId > 0) {
                                        $m = \App\Models\MediaFile::query()->whereKey($mediaId)->first();
                                        if ($m && $m->isImage()) $mediaUrl = $m->url;
                                    }
                                    $checked = $isMulti ? in_array($ov, $oldArr, true) : ((string)$oldVal === (string)$ov);
                                ?>
                                <label class="group flex gap-3 rounded-xl border border-slate-200 p-3 cursor-pointer hover:border-slate-400">
                                    <input
                                        type="<?php echo e($isMulti ? 'checkbox' : 'radio'); ?>"
                                        name="<?php echo e($isMulti ? $name.'[]' : $name); ?>"
                                        value="<?php echo e($ov); ?>"
                                        class="mt-1"
                                        <?php if($checked): echo 'checked'; endif; ?>
                                        <?php if($required && !$isMulti): ?> required <?php endif; ?>
                                    >
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-slate-900"><?php echo e($ol); ?></div>
                                        <?php if(is_array($opt) && !empty($opt['description'])): ?>
                                            <div class="mt-0.5 text-xs text-slate-600"><?php echo e($opt['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($mediaUrl): ?>
                                        <img src="<?php echo e($mediaUrl); ?>" alt="" class="h-12 w-12 rounded-lg object-cover border border-slate-200">
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php if($required && $isMulti): ?>
                            <div class="mt-1 text-xs text-slate-500">Select at least one.</div>
                        <?php endif; ?>
                    <?php elseif($type === 'phone'): ?>
                        <?php
                            $oldPhone = old($name);
                        ?>
                        <div class="mt-2 flex gap-2" data-phone-field data-phone-name="<?php echo e($name); ?>">
                            <select
                                class="w-40 rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                data-phone-country
                                aria-label="Country"
                                style="font-size:18px;"
                            ></select>
                            <input
                                type="tel"
                                class="flex-1 rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                placeholder="<?php echo e($placeholder ?: 'Phone number'); ?>"
                                data-phone-number
                                style="font-size:16px;"
                            >
                            <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($oldPhone); ?>" data-phone-hidden <?php if($required): ?> required <?php endif; ?>>
                        </div>
                        <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php else: ?>
                        <input
                            type="<?php echo e($type === 'email' ? 'email' : 'text'); ?>"
                            name="<?php echo e($name); ?>"
                            value="<?php echo e(old($name)); ?>"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                            placeholder="<?php echo e($placeholder); ?>"
                            <?php if($required): ?> required <?php endif; ?>
                        >
                    <?php endif; ?>

                    <?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-6">
            <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Send
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    // Lightweight phone input helper:
    // - Big emoji flags
    // - Dial code dropdown
    // - Auto-detect via timezone + language (best effort)

    const COUNTRY_LIST = [
        { iso: 'ZA', dial: '+27', flag: '🇿🇦', name: 'South Africa' },
        { iso: 'US', dial: '+1',  flag: '🇺🇸', name: 'United States' },
        { iso: 'GB', dial: '+44', flag: '🇬🇧', name: 'United Kingdom' },
        { iso: 'AU', dial: '+61', flag: '🇦🇺', name: 'Australia' },
        { iso: 'NZ', dial: '+64', flag: '🇳🇿', name: 'New Zealand' },
        { iso: 'CA', dial: '+1',  flag: '🇨🇦', name: 'Canada' },
        { iso: 'IE', dial: '+353',flag: '🇮🇪', name: 'Ireland' },
        { iso: 'DE', dial: '+49', flag: '🇩🇪', name: 'Germany' },
        { iso: 'FR', dial: '+33', flag: '🇫🇷', name: 'France' },
        { iso: 'ES', dial: '+34', flag: '🇪🇸', name: 'Spain' },
        { iso: 'PT', dial: '+351',flag: '🇵🇹', name: 'Portugal' },
        { iso: 'NL', dial: '+31', flag: '🇳🇱', name: 'Netherlands' },
        { iso: 'BE', dial: '+32', flag: '🇧🇪', name: 'Belgium' },
        { iso: 'CH', dial: '+41', flag: '🇨🇭', name: 'Switzerland' },
        { iso: 'IT', dial: '+39', flag: '🇮🇹', name: 'Italy' },
        { iso: 'SE', dial: '+46', flag: '🇸🇪', name: 'Sweden' },
        { iso: 'NO', dial: '+47', flag: '🇳🇴', name: 'Norway' },
        { iso: 'DK', dial: '+45', flag: '🇩🇰', name: 'Denmark' },
        { iso: 'FI', dial: '+358',flag: '🇫🇮', name: 'Finland' },
        { iso: 'AE', dial: '+971',flag: '🇦🇪', name: 'UAE' },
        { iso: 'SA', dial: '+966',flag: '🇸🇦', name: 'Saudi Arabia' },
        { iso: 'IN', dial: '+91', flag: '🇮🇳', name: 'India' },
        { iso: 'PK', dial: '+92', flag: '🇵🇰', name: 'Pakistan' },
        { iso: 'SG', dial: '+65', flag: '🇸🇬', name: 'Singapore' },
        { iso: 'MY', dial: '+60', flag: '🇲🇾', name: 'Malaysia' },
        { iso: 'ID', dial: '+62', flag: '🇮🇩', name: 'Indonesia' },
        { iso: 'PH', dial: '+63', flag: '🇵🇭', name: 'Philippines' },
        { iso: 'JP', dial: '+81', flag: '🇯🇵', name: 'Japan' },
        { iso: 'KR', dial: '+82', flag: '🇰🇷', name: 'South Korea' },
        { iso: 'CN', dial: '+86', flag: '🇨🇳', name: 'China' },
        { iso: 'HK', dial: '+852',flag: '🇭🇰', name: 'Hong Kong' },
        { iso: 'BR', dial: '+55', flag: '🇧🇷', name: 'Brazil' },
        { iso: 'MX', dial: '+52', flag: '🇲🇽', name: 'Mexico' },
        { iso: 'AR', dial: '+54', flag: '🇦🇷', name: 'Argentina' },
        { iso: 'CL', dial: '+56', flag: '🇨🇱', name: 'Chile' },
        { iso: 'NG', dial: '+234',flag: '🇳🇬', name: 'Nigeria' },
        { iso: 'KE', dial: '+254',flag: '🇰🇪', name: 'Kenya' },
        { iso: 'GH', dial: '+233',flag: '🇬🇭', name: 'Ghana' },
        { iso: 'EG', dial: '+20', flag: '🇪🇬', name: 'Egypt' },
    ];

    const TZ_TO_ISO = {
        'Africa/Johannesburg': 'ZA',
        'Africa/Cape_Town': 'ZA',
        'Africa/Pretoria': 'ZA',
        'Europe/London': 'GB',
        'Europe/Dublin': 'IE',
        'America/New_York': 'US',
        'America/Los_Angeles': 'US',
        'America/Chicago': 'US',
        'America/Toronto': 'CA',
        'Australia/Sydney': 'AU',
        'Australia/Melbourne': 'AU',
        'Pacific/Auckland': 'NZ',
    };

    function guessIso() {
        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (tz && TZ_TO_ISO[tz]) return TZ_TO_ISO[tz];
        } catch (e) {}

        try {
            const lang = (navigator.language || '').toUpperCase();
            const m = lang.match(/-([A-Z]{2})$/);
            if (m && m[1]) return m[1];
        } catch (e) {}

        return 'ZA';
    }

    function buildOptions(selectEl, isoDefault) {
        selectEl.innerHTML = '';
        COUNTRY_LIST.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.iso;
            opt.textContent = `${c.flag} ${c.dial}`;
            if (c.iso === isoDefault) opt.selected = true;
            selectEl.appendChild(opt);
        });
    }

    function countryByIso(iso) {
        return COUNTRY_LIST.find(c => c.iso === iso) || COUNTRY_LIST[0];
    }

    function normaliseDigits(input) {
        return String(input || '').replace(/[^0-9]/g, '');
    }

    function combine(iso, national) {
        const c = countryByIso(iso);
        const dial = c.dial.replace('+','');
        const digits = normaliseDigits(national);
        if (!digits) return '';
        return `+${dial}${digits}`;
    }

    document.querySelectorAll('[data-phone-field]').forEach(root => {
        const selectEl = root.querySelector('[data-phone-country]');
        const numberEl = root.querySelector('[data-phone-number]');
        const hiddenEl = root.querySelector('[data-phone-hidden]');
        if (!selectEl || !numberEl || !hiddenEl) return;

        const isoDefault = guessIso();
        buildOptions(selectEl, isoDefault);

        // Try to split an existing +countrycode number into national (best effort)
        const existing = String(hiddenEl.value || '').trim();
        if (existing.startsWith('+')) {
            // naive: if matches one of our dial codes, pick it
            for (const c of COUNTRY_LIST) {
                if (existing.startsWith(c.dial)) {
                    selectEl.value = c.iso;
                    numberEl.value = existing.slice(c.dial.length);
                    break;
                }
            }
        }

        function sync() {
            hiddenEl.value = combine(selectEl.value, numberEl.value);
        }

        selectEl.addEventListener('change', sync);
        numberEl.addEventListener('input', sync);
        sync();
    });
})();
</script>
<?php /**PATH C:\laragon\www\2kocms\resources\views/cms/forms/embed.blade.php ENDPATH**/ ?>