@props([
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
])

@php
    $uid = $uid ?: ('mp_' . \Illuminate\Support\Str::uuid()->toString());
    $inputId = $uid . '_input';
    $previewId = $uid . '_preview';
    $clearId = $uid . '_clear';

    $pickerUrl = $pickerUrl ?: route('admin.media.picker', ['type' => $type]);
    $initialValue = old($name, $value);
    $initialClear = old($clearName ?: '', '0');
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

        <div class="flex flex-wrap items-center gap-2">
            <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ $initialValue }}">

            @if($clearName)
                <input type="hidden" id="{{ $clearId }}" name="{{ $clearName }}" value="{{ $initialClear }}">
            @endif

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800"
                onclick="window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                    url: @js(route('admin.media.picker', array_merge(request()->query(), ['type' => $type, 'tab' => 'library']))),
                    onSelect: function (payload) {
                        const input = document.getElementById(@js($inputId));
                        const preview = document.getElementById(@js($previewId));
                        const clear = @js($clearName) ? document.getElementById(@js($clearId)) : null;

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
                {{ $chooseText }}
            </button>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50"
                onclick="window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                    url: @js(route('admin.media.picker', array_merge(request()->query(), ['type' => $type, 'tab' => 'upload']))),
                    onSelect: function (payload) {
                        const input = document.getElementById(@js($inputId));
                        const preview = document.getElementById(@js($previewId));
                        const clear = @js($clearName) ? document.getElementById(@js($clearId)) : null;

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
                {{ $uploadText }}
            </button>

            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-gray-50"
                onclick="
                    const input = document.getElementById(@js($inputId));
                    const preview = document.getElementById(@js($previewId));
                    const clear = @js($clearName) ? document.getElementById(@js($clearId)) : null;

                    if (input) input.value = '';
                    if (preview) { preview.src=''; preview.classList.add('hidden'); }
                    if (clear) clear.value = '1';
                "
            >
                {{ $clearText }}
            </button>
        </div>
    </div>
</div>
