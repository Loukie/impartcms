@props([
    'name',
    'label' => 'Choose from Media library',
    'value' => null,
    'previewUrl' => null,
    'accept' => 'images', // images|fonts|docs|all
    'buttonText' => 'Choose from Media library',
    'clearText' => 'Clear',
    'help' => null,
])

@php
    $uuid = (string) \Illuminate\Support\Str::uuid();
    $inputId = 'media_picker_input_' . $uuid;
    $previewId = 'media_picker_preview_' . $uuid;
    $titleId = 'media_picker_title_' . $uuid;

    $currentValue = old($name, $value);
    $currentPreview = $previewUrl;

    $baseUrl = route('admin.media.picker', ['accept' => $accept]);
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>
        <div class="flex items-center gap-2">
            <button type="button"
                    class="inline-flex items-center px-3 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800"
                    data-media-picker-open="{{ $uuid }}">
                {{ $buttonText }}
            </button>

            <button type="button"
                    class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50"
                    data-media-picker-clear="{{ $uuid }}">
                {{ $clearText }}
            </button>
        </div>
    </div>

    <input id="{{ $inputId }}" type="hidden" name="{{ $name }}" value="{{ $currentValue }}">

    <div class="mt-3 flex items-center gap-4">
        <div class="h-12 w-12 rounded border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
            @if(!empty($currentPreview))
                <img id="{{ $previewId }}" src="{{ $currentPreview }}" alt="Selected media" class="h-full w-full object-cover" />
            @else
                <img id="{{ $previewId }}" src="" alt="" class="hidden h-full w-full object-cover" />
                <div class="text-[11px] text-gray-500" data-media-picker-placeholder="{{ $uuid }}">None</div>
            @endif
        </div>

        <div class="min-w-0">
            <div id="{{ $titleId }}" class="text-sm text-gray-700 truncate">
                @if(!empty($currentValue))
                    Selected media ID: <span class="font-mono">{{ $currentValue }}</span>
                @else
                    No media selected
                @endif
            </div>
            @if(!empty($help))
                <div class="mt-1 text-xs text-gray-500">{{ $help }}</div>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    const uuid = @json($uuid);
    const input = document.getElementById(@json($inputId));
    const preview = document.getElementById(@json($previewId));
    const title = document.getElementById(@json($titleId));

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
        const url = @json($baseUrl) + selected;

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
