<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'label' => 'Choose from Media library',
    'value' => null,
    'previewUrl' => null,
    'accept' => 'images', // images|fonts|docs|all
    'buttonText' => 'Choose from Media library',
    'clearText' => 'Clear',
    'help' => null,
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
    'label' => 'Choose from Media library',
    'value' => null,
    'previewUrl' => null,
    'accept' => 'images', // images|fonts|docs|all
    'buttonText' => 'Choose from Media library',
    'clearText' => 'Clear',
    'help' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $uuid = (string) \Illuminate\Support\Str::uuid();
    $inputId = 'media_picker_input_' . $uuid;
    $previewId = 'media_picker_preview_' . $uuid;
    $titleId = 'media_picker_title_' . $uuid;

    $currentValue = old($name, $value);
    $currentPreview = $previewUrl;

    $baseUrl = route('admin.media.picker', ['accept' => $accept]);
?>

<div <?php echo e($attributes->merge(['class' => ''])); ?>>
    <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700"><?php echo e($label); ?></label>
        <div class="flex items-center gap-2">
            <button type="button"
                    class="inline-flex items-center px-3 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800"
                    data-media-picker-open="<?php echo e($uuid); ?>">
                <?php echo e($buttonText); ?>

            </button>

            <button type="button"
                    class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50"
                    data-media-picker-clear="<?php echo e($uuid); ?>">
                <?php echo e($clearText); ?>

            </button>
        </div>
    </div>

    <input id="<?php echo e($inputId); ?>" type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($currentValue); ?>">

    <div class="mt-3 flex items-center gap-4">
        <div class="h-12 w-12 rounded border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
            <?php if(!empty($currentPreview)): ?>
                <img id="<?php echo e($previewId); ?>" src="<?php echo e($currentPreview); ?>" alt="Selected media" class="h-full w-full object-cover" />
            <?php else: ?>
                <img id="<?php echo e($previewId); ?>" src="" alt="" class="hidden h-full w-full object-cover" />
                <div class="text-[11px] text-gray-500" data-media-picker-placeholder="<?php echo e($uuid); ?>">None</div>
            <?php endif; ?>
        </div>

        <div class="min-w-0">
            <div id="<?php echo e($titleId); ?>" class="text-sm text-gray-700 truncate">
                <?php if(!empty($currentValue)): ?>
                    Selected media ID: <span class="font-mono"><?php echo e($currentValue); ?></span>
                <?php else: ?>
                    No media selected
                <?php endif; ?>
            </div>
            <?php if(!empty($help)): ?>
                <div class="mt-1 text-xs text-gray-500"><?php echo e($help); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    const uuid = <?php echo json_encode($uuid, 15, 512) ?>;
    const input = document.getElementById(<?php echo json_encode($inputId, 15, 512) ?>);
    const preview = document.getElementById(<?php echo json_encode($previewId, 15, 512) ?>);
    const title = document.getElementById(<?php echo json_encode($titleId, 15, 512) ?>);

    const openBtn = document.querySelector('[data-media-picker-open="' + uuid + '"]');
    const clearBtn = document.querySelector('[data-media-picker-clear="' + uuid + '"]');
    const placeholder = document.querySelector('[data-media-picker-placeholder="' + uuid + '"]');

    if (!openBtn || !clearBtn || !input || !preview || !title) return;

    function setEmpty() {
        input.value = '';
        title.textContent = 'No media selected';
        preview.src = '';
        preview.classList.add('hidden');
        if (placeholder) placeholder.classList.remove('hidden');
    }

    function setSelected(payload) {
        // payload: {id, url, title, original_name}
        input.value = String(payload.id || '');
        title.textContent = (payload.title || payload.original_name || ('Selected media ID: ' + input.value));

        if (payload.url) {
            preview.src = payload.url;
            preview.classList.remove('hidden');
            if (placeholder) placeholder.classList.add('hidden');
        }
    }

    openBtn.addEventListener('click', () => {
        if (!window.ImpartMediaPicker || typeof window.ImpartMediaPicker.open !== 'function') return;
        const selected = input.value ? ('&selected=' + encodeURIComponent(input.value)) : '';
        const url = <?php echo json_encode($baseUrl, 15, 512) ?> + selected;

        window.ImpartMediaPicker.open({
            url,
            onSelect: setSelected,
        });
    });

    clearBtn.addEventListener('click', () => {
        setEmpty();
    });
})();
</script>
<?php /**PATH C:\laragon\www\2kocms\resources\views/components/admin/media-picker.blade.php ENDPATH**/ ?>