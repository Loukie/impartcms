@props([
    'name',
    'value' => null,
    'label' => 'Choose an icon',
    'buttonText' => 'Choose icon',
    'clearText' => 'Clear',
    'uid' => null,
])

@php
    $uid = $uid ?: ('ip_' . \Illuminate\Support\Str::uuid()->toString());
    $inputId = $uid . '_input';
    $previewId = $uid . '_preview';
    $initialValue = old($name, $value);
@endphp

<div class="space-y-2">
    <div class="text-sm font-semibold text-gray-900">{{ $label }}</div>

    <div class="flex items-center gap-3">
        <div class="w-20 h-20 rounded-md border bg-white flex items-center justify-center overflow-hidden">
            <div id="{{ $previewId }}" class="text-gray-700"></div>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ $initialValue }}">

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800"
                onclick="window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                    url: @js(route('admin.media.picker', ['type' => 'icons', 'allow' => 'icons'])),
                    onSelect: function (payload) {
                        if (!payload || payload.kind !== 'icon' || !payload.icon) return;

                        const input = document.getElementById(@js($inputId));
                        const preview = document.getElementById(@js($previewId));
                        if (!input || !preview) return;

                        input.value = JSON.stringify(payload.icon);

                        const kind = (payload.icon.kind || '').toLowerCase();
                        const size = parseInt(payload.icon.size || 24, 10) || 24;
                        const colour = payload.icon.colour || '#111827';

                        if (kind === 'fa') {
                            preview.innerHTML = `<i class="${payload.icon.value}" style="font-size:${size}px;color:${colour};line-height:1"></i>`;
                        } else if (kind === 'lucide') {
                            preview.innerHTML = `<i data-lucide="${payload.icon.value}" style="width:${size}px;height:${size}px;color:${colour};display:inline-block"></i>`;
                            window.ImpartLucide && window.ImpartLucide.render(preview);
                        } else {
                            preview.innerHTML = '';
                        }
                    }
                })"
            >
                {{ $buttonText }}
            </button>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50"
                onclick="
                    const input = document.getElementById(@js($inputId));
                    const preview = document.getElementById(@js($previewId));
                    if (input) input.value = '';
                    if (preview) preview.innerHTML = '';
                "
            >
                {{ $clearText }}
            </button>
        </div>
    </div>

    <div class="text-xs text-gray-500">
        Shortcode: <code class="px-1 py-0.5 bg-gray-100 rounded">[icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]</code>
        or <code class="px-1 py-0.5 bg-gray-100 rounded">[icon kind="lucide" value="home" size="24" colour="#111827"]</code>
    </div>
</div>

<script>
(function initIconPickerPreview() {
    const input = document.getElementById(@js($inputId));
    const preview = document.getElementById(@js($previewId));
    if (!input || !preview) return;

    try {
        const raw = (input.value || '').trim();
        if (!raw) return;
        const icon = JSON.parse(raw);
        const kind = (icon.kind || '').toLowerCase();
        const size = parseInt(icon.size || 24, 10) || 24;
        const colour = icon.colour || '#111827';

        if (kind === 'fa') {
            preview.innerHTML = `<i class="${icon.value}" style="font-size:${size}px;color:${colour};line-height:1"></i>`;
        } else if (kind === 'lucide') {
            preview.innerHTML = `<i data-lucide="${icon.value}" style="width:${size}px;height:${size}px;color:${colour};display:inline-block"></i>`;
            window.ImpartLucide && window.ImpartLucide.render(preview);
        }
    } catch (e) {
        // ignore
    }
})();
</script>
