<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'value' => null,
    'label' => 'Choose from Media library',
    'previewUrl' => null,
    'type' => 'images', // images | docs (icons handled separately)
    'pickerUrl' => null,
    'chooseText' => 'Choose from Media Library',
    'uploadText' => 'Upload',
    'clearText' => 'Clear',
    'clearName' => null, // hidden boolean input to signal an explicit clear action
    'uid' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'name',
    'value' => null,
    'label' => 'Choose from Media library',
    'previewUrl' => null,
    'type' => 'images', // images | docs (icons handled separately)
    'pickerUrl' => null,
    'chooseText' => 'Choose from Media Library',
    'uploadText' => 'Upload',
    'clearText' => 'Clear',
    'clearName' => null, // hidden boolean input to signal an explicit clear action
    'uid' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $uid = $uid ?: ('mp_' . \Illuminate\Support\Str::uuid()->toString());
    $inputId = $uid . '_input';
    $previewId = $uid . '_preview';
    $clearId = $uid . '_clear';

    $pickerUrl = $pickerUrl ?: route('admin.media.picker', ['type' => $type]);
    $initialValue = old($name, $value);
    $initialClear = old($clearName ?: '', '0');
?>

<div class="space-y-2">
    <div class="text-sm font-semibold text-gray-900"><?php echo e($label); ?></div>

    <div class="flex items-center gap-3">
        <div class="w-20 h-20 rounded-md border bg-white flex items-center justify-center overflow-hidden">
            <?php if($previewUrl): ?>
                <img id="<?php echo e($previewId); ?>" src="<?php echo e($previewUrl); ?>" alt="" class="w-full h-full object-contain">
            <?php else: ?>
                <img id="<?php echo e($previewId); ?>" src="" alt="" class="hidden w-full h-full object-contain">
                <div class="text-xs text-gray-400">No image</div>
            <?php endif; ?>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <input type="hidden" id="<?php echo e($inputId); ?>" name="<?php echo e($name); ?>" value="<?php echo e($initialValue); ?>">

            <?php if($clearName): ?>
                <input type="hidden" id="<?php echo e($clearId); ?>" name="<?php echo e($clearName); ?>" value="<?php echo e($initialClear); ?>">
            <?php endif; ?>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800"
                onclick="window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                    url: <?php echo \Illuminate\Support\Js::from(route('admin.media.picker', array_merge(request()->query(), ['type' => $type, 'tab' => 'library'])))->toHtml() ?>,
                    onSelect: function (payload) {
                        const input = document.getElementById(<?php echo \Illuminate\Support\Js::from($inputId)->toHtml() ?>);
                        const preview = document.getElementById(<?php echo \Illuminate\Support\Js::from($previewId)->toHtml() ?>);
                        const clear = <?php echo \Illuminate\Support\Js::from($clearName)->toHtml() ?> ? document.getElementById(<?php echo \Illuminate\Support\Js::from($clearId)->toHtml() ?>) : null;

                        if (!input) return;

                        input.value = payload.id || '';

                        if (preview) {
                            if (payload.url) {
                                preview.src = payload.url;
                                preview.classList.remove('hidden');
                            } else {
                                preview.src = '';
                                preview.classList.add('hidden');
                            }
                        }

                        if (clear) clear.value = '0';
                    }
                })"
            >
                <?php echo e($chooseText); ?>

            </button>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50"
                onclick="window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                    url: <?php echo \Illuminate\Support\Js::from(route('admin.media.picker', array_merge(request()->query(), ['type' => $type, 'tab' => 'upload'])))->toHtml() ?>,
                    onSelect: function (payload) {
                        const input = document.getElementById(<?php echo \Illuminate\Support\Js::from($inputId)->toHtml() ?>);
                        const preview = document.getElementById(<?php echo \Illuminate\Support\Js::from($previewId)->toHtml() ?>);
                        const clear = <?php echo \Illuminate\Support\Js::from($clearName)->toHtml() ?> ? document.getElementById(<?php echo \Illuminate\Support\Js::from($clearId)->toHtml() ?>) : null;

                        if (!input) return;

                        input.value = payload.id || '';

                        if (preview) {
                            if (payload.url) {
                                preview.src = payload.url;
                                preview.classList.remove('hidden');
                            } else {
                                preview.src = '';
                                preview.classList.add('hidden');
                            }
                        }

                        if (clear) clear.value = '0';
                    }
                })"
            >
                <?php echo e($uploadText); ?>

            </button>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50"
                onclick="
                    const input = document.getElementById(<?php echo \Illuminate\Support\Js::from($inputId)->toHtml() ?>);
                    const preview = document.getElementById(<?php echo \Illuminate\Support\Js::from($previewId)->toHtml() ?>);
                    const clear = <?php echo \Illuminate\Support\Js::from($clearName)->toHtml() ?> ? document.getElementById(<?php echo \Illuminate\Support\Js::from($clearId)->toHtml() ?>) : null;

                    if (input) input.value = '';
                    if (preview) { preview.src=''; preview.classList.add('hidden'); }
                    if (clear) clear.value = '1';
                "
            >
                <?php echo e($clearText); ?>

            </button>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\2kocms\resources\views/components/admin/media-picker.blade.php ENDPATH**/ ?>