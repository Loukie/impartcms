@props([
    'label' => 'Select',
    // Hidden inputs
    'mediaName',
    'iconName',
    // Optional clear checkbox/input name (set to 1 on clear)
    'clearName' => null,
    // Current values
    'mediaId' => null,
    'mediaUrl' => null,
    'iconJson' => null,
    // Picker options
    'allow' => 'images,icons',
    'uploadEnabled' => true,
    // UI
    'help' => null,
])

@php
    $uid = 'mi_' . substr(md5($mediaName . '|' . $iconName . '|' . uniqid('', true)), 0, 10);

    $oldMediaId = old($mediaName, $mediaId);
    $oldIconJson = old($iconName, $iconJson);

    $hasIcon = !empty($oldIconJson);
    $hasMedia = !$hasIcon && !empty($mediaUrl) && !empty($oldMediaId);

    $iconHtml = '';
    if ($hasIcon) {
        $iconHtml = \App\Support\IconRenderer::renderHtml($oldIconJson, 40, '#111827');
    }

    $pickerBase = route('admin.media.picker');
@endphp

<div class="space-y-2">
    <div>
        <div class="text-sm font-semibold text-slate-900">{{ $label }}</div>
        @if($help)
            <div class="text-xs text-slate-500 mt-0.5">{{ $help }}</div>
        @endif
    </div>

    <div class="flex items-center gap-3 flex-wrap">
        <div class="h-16 w-16 rounded-lg border bg-white flex items-center justify-center overflow-hidden">
            <img id="{{ $uid }}_img" src="{{ $hasMedia ? $mediaUrl : '' }}" alt="" class="max-h-full max-w-full object-contain {{ $hasMedia ? '' : 'hidden' }}" />
            <div id="{{ $uid }}_icon" class="{{ $hasIcon ? '' : 'hidden' }}">{!! $iconHtml !!}</div>
            <div id="{{ $uid }}_empty" class="text-[11px] text-slate-400 {{ (!$hasMedia && !$hasIcon) ? '' : 'hidden' }}">No selection</div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" id="{{ $uid }}_choose"
                    class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                Choose from Media Library
            </button>

            @if($uploadEnabled)
                <button type="button" id="{{ $uid }}_upload"
                        class="px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-slate-50">
                    Upload
                </button>
            @endif

            <button type="button" id="{{ $uid }}_clear"
                    class="px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-slate-50">
                Clear
            </button>

            <button type="button" id="{{ $uid }}_copy"
                    class="px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-slate-50 hidden">
                Copy shortcode
            </button>
        </div>
    </div>

    <div id="{{ $uid }}_shortcode_wrap" class="hidden">
        <div class="text-xs text-slate-500">Shortcode</div>
        <div class="mt-1 flex items-center gap-2 flex-wrap">
            <code id="{{ $uid }}_shortcode" class="px-2 py-1 bg-white border rounded-md text-[11px] font-mono break-all"></code>
        </div>
    </div>

    <input type="hidden" id="{{ $uid }}_media" name="{{ $mediaName }}" value="{{ $oldMediaId }}" />
    <input type="hidden" id="{{ $uid }}_icon_json" name="{{ $iconName }}" value="{{ $oldIconJson }}" />
    @if($clearName)
        <input type="hidden" id="{{ $uid }}_clear_flag" name="{{ $clearName }}" value="0" />
    @endif

    @error($mediaName)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error($iconName)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
(function () {
    const uid = @js($uid);
    const pickerBase = @js($pickerBase);
    const allow = @js($allow);

    const elImg = document.getElementById(uid + '_img');
    const elIcon = document.getElementById(uid + '_icon');
    const elEmpty = document.getElementById(uid + '_empty');

    const inMedia = document.getElementById(uid + '_media');
    const inIcon = document.getElementById(uid + '_icon_json');
    const inClear = document.getElementById(uid + '_clear_flag');

    const btnChoose = document.getElementById(uid + '_choose');
    const btnUpload = document.getElementById(uid + '_upload');
    const btnClear = document.getElementById(uid + '_clear');
    const btnCopy = document.getElementById(uid + '_copy');

    const scWrap = document.getElementById(uid + '_shortcode_wrap');
    const scCode = document.getElementById(uid + '_shortcode');

    function normaliseHexColour(input) {
        const s = String(input || '').trim();
        if (!s) return '#111827';
        if (s[0] === '#') return s.length === 7 ? s : ('#' + s.slice(1).padEnd(6, '0').slice(0, 6));
        return ('#' + s.padEnd(6, '0').slice(0, 6));
    }

    function buildIconShortcodeFromObj(obj) {
        const kind = String((obj && obj.kind) ? obj.kind : '').toLowerCase();
        const value = String((obj && obj.value) ? obj.value : '');
        const size = parseInt((obj && obj.size) ? obj.size : 24, 10) || 24;
        const colour = normaliseHexColour((obj && (obj.colour || obj.color)) ? (obj.colour || obj.color) : '#111827');

        if (!kind || !value) return '';
        // Compact + readable shortcode (supported by App\Support\Cms)
        return `[icon kind="${kind}" value="${value}" size="${size}" colour="${colour}"]`;
    }


    async function copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            try {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                return true;
            } catch (e2) {
                return false;
            }
        }
    }

    function hideShortcode() {
        if (btnCopy) btnCopy.classList.add('hidden');
        if (scWrap) scWrap.classList.add('hidden');
        if (scCode) scCode.textContent = '';
    }

    function showShortcodeForObj(obj) {
        const sc = buildIconShortcodeFromObj(obj);
        if (btnCopy) btnCopy.classList.remove('hidden');
        if (scWrap) scWrap.classList.remove('hidden');
        if (scCode) scCode.textContent = sc;
    }

    function showEmpty() {
        if (elImg) elImg.classList.add('hidden');
        if (elIcon) elIcon.classList.add('hidden');
        if (elEmpty) elEmpty.classList.remove('hidden');
        if (elImg) elImg.src = '';
        if (elIcon) elIcon.innerHTML = '';
        hideShortcode();
    }

    function showImage(url) {
        if (!elImg) return;
        elImg.src = url || '';
        elImg.classList.remove('hidden');
        if (elIcon) elIcon.classList.add('hidden');
        if (elEmpty) elEmpty.classList.add('hidden');
        hideShortcode();
    }

    function renderIconFromJson(jsonStr) {
        if (!elIcon) return;
        elIcon.innerHTML = '';

        let obj = null;
        try { obj = JSON.parse(String(jsonStr || '').trim()); } catch (e) { obj = null; }
        if (!obj || typeof obj !== 'object') {
            showEmpty();
            return;
        }

        showShortcodeForObj(obj);

        const kind = String(obj.kind || '').toLowerCase();
        const value = String(obj.value || '');
        const size = parseInt(obj.size || 40, 10) || 40;
        const colour = String(obj.colour || obj.color || '#111827');

        if (kind === 'fa') {
            if (obj.svg && typeof obj.svg === 'string' && obj.svg.trim().startsWith('<svg')) {
                elIcon.innerHTML = obj.svg;
                const s = elIcon.querySelector('svg');
                if (s) {
                    s.removeAttribute('width');
                    s.removeAttribute('height');
                    s.style.width = size + 'px';
                    s.style.height = size + 'px';
                    s.style.display = 'block';
                    s.style.color = colour;
                }
            } else {
                const i = document.createElement('i');
                i.className = value;
                i.style.fontSize = size + 'px';
                i.style.color = colour;
                i.style.lineHeight = '1';
                elIcon.appendChild(i);
            }
        } else if (kind === 'lucide') {
            const i = document.createElement('i');
            i.setAttribute('data-lucide', value);
            i.style.width = size + 'px';
            i.style.height = size + 'px';
            i.style.color = colour;
            i.style.display = 'block';
            elIcon.appendChild(i);
            if (window.ImpartLucide && typeof window.ImpartLucide.render === 'function') {
                window.ImpartLucide.render(elIcon);
            }
        } else {
            showEmpty();
            return;
        }

        elIcon.classList.remove('hidden');
        if (elImg) elImg.classList.add('hidden');
        if (elEmpty) elEmpty.classList.add('hidden');
    }

    function openPicker(tab) {
        if (!window.ImpartMediaPicker || typeof window.ImpartMediaPicker.open !== 'function') return;

        const u = new URL(pickerBase, window.location.origin);
        u.searchParams.set('type', 'images');
        u.searchParams.set('tab', tab || 'library');
        if (allow) u.searchParams.set('allow', allow);

        window.ImpartMediaPicker.open({
            url: u.toString(),
            onSelect: (payload) => {
                try {
                    if (payload && payload.kind === 'icon' && payload.icon) {
                        inIcon.value = JSON.stringify(payload.icon);
                        inMedia.value = '';
                        if (inClear) inClear.value = '0';
                        renderIconFromJson(inIcon.value);
                        return;
                    }

                    // Media selection
                    if (payload && payload.id && payload.url) {
                        inMedia.value = String(payload.id);
                        inIcon.value = '';
                        if (inClear) inClear.value = '0';
                        showImage(payload.url);
                        return;
                    }
                } catch (e) {
                    // ignore
                }
            }
        });
    }

    if (btnChoose) btnChoose.addEventListener('click', () => openPicker('library'));
    if (btnUpload) btnUpload.addEventListener('click', () => openPicker('upload'));

    if (btnClear) btnClear.addEventListener('click', () => {
        inMedia.value = '';
        inIcon.value = '';
        if (inClear) inClear.value = '1';
        showEmpty();
    });

    if (btnCopy) btnCopy.addEventListener('click', async () => {
        try {
            const raw = String(inIcon?.value || '').trim();
            if (!raw) return;
            const obj = JSON.parse(raw);
            const sc = buildIconShortcodeFromObj(obj);
            const ok = await copyToClipboard(sc);
            if (btnCopy) {
                const old = btnCopy.textContent;
                btnCopy.textContent = ok ? 'Copied ✔' : 'Copy failed';
                setTimeout(() => { btnCopy.textContent = old; }, 1200);
            }
        } catch (e) {
            // ignore
        }
    });

    // Initial hydrate if icon JSON exists (keeps preview consistent even if server-side HTML changes).
    if (inIcon && String(inIcon.value || '').trim() !== '') {
        renderIconFromJson(inIcon.value);
    } else if (elImg && elImg.getAttribute('src')) {
        showImage(elImg.getAttribute('src'));
    } else {
        showEmpty();
    }
})();
</script>
