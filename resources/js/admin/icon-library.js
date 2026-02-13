/**
 * ImpartCMS Icon Library Renderer (Font Awesome only)
 *
 * Activates only when a page contains:
 *   <div id="impart-icon-library"></div>
 *
 * Expected DOM structure:
 * - #impart-fa-grid
 * - inputs:
 *   - #impart-icon-search
 *   - #impart-fa-style
 *   - #impart-icon-size
 *   - #impart-icon-colour
 *   - #impart-icon-loadmore-fa
 *
 * Behaviour:
 * - In the media picker iframe: click → posts selection to parent.
 * - On the Media page: click → copies icon class to clipboard + toast.
 */

function qs(sel, root = document) {
    return root.querySelector(sel);
}

function isInIframe() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

function safeOrigin() {
    try {
        return window.location.origin;
    } catch (e) {
        return '*';
    }
}

function postSelected(payload) {
    try {
        window.parent.postMessage(
            { type: 'impartcms-media-selected', payload },
            safeOrigin()
        );
    } catch (e) {
        // ignore
    }
}

function isFaIconDefinition(v) {
    return (
        v &&
        typeof v === 'object' &&
        typeof v.iconName === 'string' &&
        typeof v.prefix === 'string' &&
        Array.isArray(v.icon)
    );
}

const FA_STYLE = {
    solid: {
        pack: () => import('@fortawesome/free-solid-svg-icons'),
        classPrefix: 'fa-solid',
    },
    regular: {
        pack: () => import('@fortawesome/free-regular-svg-icons'),
        classPrefix: 'fa-regular',
    },
    brands: {
        pack: () => import('@fortawesome/free-brands-svg-icons'),
        classPrefix: 'fa-brands',
    },
};

let faItemsPromise = null;

async function loadFaItems() {
    if (faItemsPromise) return faItemsPromise;

    faItemsPromise = (async () => {
        const items = [];
        const seen = new Set();

        for (const style of Object.keys(FA_STYLE)) {
            const { pack, classPrefix } = FA_STYLE[style];
            const mod = await pack();

            for (const def of Object.values(mod)) {
                if (!isFaIconDefinition(def)) continue;

                const name = def.iconName;
                const key = `${style}:${name}`;
                if (seen.has(key)) continue;
                seen.add(key);

                items.push({
                    kind: 'fa',
                    name,
                    style,
                    className: `${classPrefix} fa-${name}`,
                });
            }
        }

        items.sort((a, b) =>
            a.name === b.name
                ? a.style.localeCompare(b.style)
                : a.name.localeCompare(b.name)
        );

        return items;
    })();

    return faItemsPromise;
}

function normaliseHexColour(v) {
    const c = (v || '').trim();
    return /^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(c) ? c : '#111827';
}

function clampNumber(v, min, max, fallback) {
    const n = parseInt(String(v || ''), 10);
    if (Number.isNaN(n)) return fallback;
    return Math.min(max, Math.max(min, n));
}

function createTile({ label, html, onClick }) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className =
        'group bg-white border rounded-xl p-2 hover:shadow-sm transition flex flex-col items-center gap-2';

    const icon = document.createElement('div');
    icon.className =
        'w-full aspect-square bg-slate-50 rounded-lg flex items-center justify-center overflow-hidden';
    icon.innerHTML = html;

    const name = document.createElement('div');
    name.className = 'w-full text-[11px] text-slate-600 truncate text-center';
    name.textContent = label;

    btn.appendChild(icon);
    btn.appendChild(name);

    btn.addEventListener('click', onClick);
    return btn;
}

function showToast(root, text) {
    const el = qs('#impart-icon-toast', root) || qs('#impart-icon-toast');
    if (!el) return;

    el.textContent = text;
    el.classList.remove('hidden');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => el.classList.add('hidden'), 1600);
}

async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch (e) {
        // fallback
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', 'readonly');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (e2) {
            return false;
        }
    }
}

function mount() {
    const root = qs('#impart-icon-library');
    if (!root) return;

    const faGrid = qs('#impart-fa-grid', root);
    const inpSearch = qs('#impart-icon-search', root);
    const selFaStyle = qs('#impart-fa-style', root);
    const inpSize = qs('#impart-icon-size', root);
    const inpColour = qs('#impart-icon-colour', root);
    const btnMoreFa = qs('#impart-icon-loadmore-fa', root);

    if (!faGrid || !inpSearch || !selFaStyle || !inpSize || !inpColour) return;

    const mode = (root.dataset.mode || '').toLowerCase() || (isInIframe() ? 'select' : 'copy');
    const inIframe = isInIframe();

    let faItems = [];
    let faLimit = 220;

    function filterName(name, q) {
        if (!q) return true;
        return name.toLowerCase().includes(q.toLowerCase());
    }

    function renderFa() {
        const q = (inpSearch.value || '').trim();
        const style = (selFaStyle.value || 'all').trim();

        faGrid.innerHTML = '';

        const filtered = faItems.filter((it) => {
            if (style !== 'all' && it.style !== style) return false;
            return filterName(it.name, q);
        });

        const slice = filtered.slice(0, faLimit);

        const size = clampNumber(inpSize.value, 8, 256, 24);
        const colour = normaliseHexColour(inpColour.value);

        for (const it of slice) {
            const tile = createTile({
                label: `${it.name} (${it.style})`,
                html: `<i class="${it.className}" style="font-size:${size}px;color:${colour};line-height:1"></i>`,
                onClick: async () => {
                    const payload = {
                        kind: 'icon',
                        icon: {
                            kind: 'fa',
                            name: it.name,
                            style: it.style,
                            value: it.className,
                            size,
                            colour,
                        },
                    };

                    if (inIframe || mode === 'select') {
                        postSelected(payload);
                        return;
                    }

                    const ok = await copyToClipboard(it.className);
                    showToast(root, ok ? `Copied: ${it.className}` : `Copy failed: ${it.className}`);
                },
            });

            faGrid.appendChild(tile);
        }

        btnMoreFa?.classList.toggle('hidden', filtered.length <= faLimit);
    }

    async function boot() {
        faGrid.innerHTML = '<div class="text-sm text-slate-500 p-2">Loading Font Awesome icons…</div>';

        try {
            faItems = await loadFaItems();
        } catch (e) {
            faItems = [];
            faGrid.innerHTML =
                '<div class="text-sm text-red-600 p-2">Font Awesome icon packs are not installed. Run: <code>npm i @fortawesome/free-solid-svg-icons @fortawesome/free-regular-svg-icons @fortawesome/free-brands-svg-icons</code></div>';
            return;
        }

        renderFa();
    }

    // Events
    inpSearch.addEventListener('input', () => renderFa());
    selFaStyle.addEventListener('change', () => renderFa());
    inpSize.addEventListener('change', () => renderFa());
    inpColour.addEventListener('change', () => renderFa());

    btnMoreFa?.addEventListener('click', () => {
        faLimit += 220;
        renderFa();
    });

    void boot();
}

// Mount on ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
