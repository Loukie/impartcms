@props([
    'name',
    'value' => null,
    'label' => 'Choose from Media library',
    'previewUrl' => null,
    'pickerUrl' => null,
    'clearCheckboxId' => null,
    'uid' => null,
])

@php
    $uid = $uid ?: ('mp_' . \Illuminate\Support\Str::uuid()->toString());
    $inputId = $uid . '_input';
    $previewId = $uid . '_preview';

    $pickerUrl = $pickerUrl ?: route('admin.media.picker');
    $initialValue = old($name, $value);
@endphp

<div class="space-y-2">
    <div class="text-sm font-semibold text-gray-900">{{ $label }}</div>

    <div class="flex items-center gap-3">
        <div class="w-20 h-20 rounded-md border bg-white flex items-center justify-center overflow-hidden">
            @if($previewUrl)
                <img id="{{ $previewId }}" src="{{ $previewUrl }}" alt="" class="w-full h-full object-contain">
            @else
                <img id="{{ $previewId }}" src="" alt="" class="hidden w-full h-full object-contain">
                <div class="text-xs text-gray-400">No image</div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ $initialValue }}">

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800"
                onclick="window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                    url: @js($pickerUrl),
                    onSelect: function (payload) {
                        const input = document.getElementById(@js($inputId));
                        const preview = document.getElementById(@js($previewId));
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

                        // If a remove checkbox exists, untick it when selecting from Media.
                        const clearCbId = @js($clearCheckboxId);
                        if (clearCbId) {
                            const cb = document.getElementById(clearCbId);
                            if (cb) cb.checked = false;
                        }
                    }
                })"
            >
                Choose from Media Library
            </button>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50"
                onclick="
                    const input = document.getElementById(@js($inputId));
                    const preview = document.getElementById(@js($previewId));
                    if (input) input.value = '';
                    if (preview) { preview.src=''; preview.classList.add('hidden'); }
                "
            >
                Clear
            </button>
        </div>
    </div>
</div>
