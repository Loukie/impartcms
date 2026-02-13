/**
 * ImpartCMS Icon Library Renderer
 *
 * Activates only when a page contains:
 *   <div id="impart-icon-library"></div>
 *
 * Expected DOM structure:
 * - #impart-fa-grid
 * - #impart-lucide-grid
 * - inputs:
 *   - #impart-icon-search
 *   - #impart-fa-style
 *   - #impart-icon-size
 *   - #impart-icon-colour
 *   - #impart-icon-loadmore-fa
 *   - #impart-icon-loadmore-lucide
 *
 * IMPORTANT:
 * We do NOT import Font Awesome metadata JSON files.
 * Newer Font Awesome packages can block deep JSON imports via `exports`,
 * which causes Vite/Rollup resolution failures. Instead we dynamically
 * import the free icon packs and build the list in JS.
 */

import { createIcons, icons as lucideIcons } from 'lucide';

function qs(sel, root = document) {
    return root.querySelector(sel);
}

function safeOrigin() {
    try {
        return window.location.origin;
    } catch (e) {
        return '*';
    }
}

function postSelected(payload) {
    // Picker is rendered inside an iframe (admin modal). We send to parent.
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

        // Sort predictable
        items.sort((a, b) =>
            a.name === b.name
                ? a.style.localeCompare(b.style)
                : a.name.localeCompare(b.name)
        );

        return items;
    })();

    return faItemsPromise;
}

function normaliseLucideItems() {
    const names = Object.keys(lucideIcons || {});
    names.sort();
    return names.map((name) => ({ kind: 'lucide', name }));
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

function mount() {
    const root = qs('#impart-icon-library');
    if (!root) return;

    const faGrid = qs('#impart-fa-grid', root);
    const lucideGrid = qs('#impart-lucide-grid', root);

    const inpSearch = qs('#impart-icon-search', root);
    const selFaStyle = qs('#impart-fa-style', root);
    const inpSize = qs('#impart-icon-size', root);
    const inpColour = qs('#impart-icon-colour', root);

    const btnMoreFa = qs('#impart-icon-loadmore-fa', root);
    const btnMoreLucide = qs('#impart-icon-loadmore-lucide', root);

    if (!faGrid || !lucideGrid || !inpSearch || !selFaStyle || !inpSize || !inpColour) return;

    const lucideItems = normaliseLucideItems();

    let faItems = [];
    let faLimit = 160;
    let lucideLimit = 160;

    function getSize() {
        const n = parseInt(inpSize.value || '24', 10);
        if (Number.isNaN(n)) return 24;
        return Math.min(256, Math.max(8, n));
    }

    function getColour() {
        const c = (inpColour.value || '#111827').trim();
        return /^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(c)
            ? c
            : '#111827';
    }

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

        for (const it of slice) {
            const size = getSize();
            const colour = getColour();

            const tile = createTile({
                label: `${it.name} (${it.style})`,
                html: `<i class="${it.className}" style="font-size:${size}px;color:${colour};line-height:1"></i>`,
                onClick: () => {
                    postSelected({
                        kind: 'icon',
                        icon: {
                            kind: 'fa',
                            name: it.name,
                            style: it.style,
                            value: it.className,
                            size,
                            colour,
                        },
                    });
                },
            });

            faGrid.appendChild(tile);
        }

        btnMoreFa?.classList.toggle('hidden', filtered.length <= faLimit);
    }

    function renderLucide() {
        const q = (inpSearch.value || '').trim();

        lucideGrid.innerHTML = '';

        const filtered = lucideItems.filter((it) => filterName(it.name, q));
        const slice = filtered.slice(0, lucideLimit);

        for (const it of slice) {
            const size = getSize();
            const colour = getColour();

            const tile = createTile({
                label: it.name,
                html: `<i data-lucide="${it.name}" style="width:${size}px;height:${size}px;color:${colour};display:inline-block"></i>`,
                onClick: () => {
                    postSelected({
                        kind: 'icon',
                        icon: {
                            kind: 'lucide',
                            name: it.name,
                            value: it.name,
                            size,
                            colour,
                        },
                    });
                },
            });

            lucideGrid.appendChild(tile);
        }

        // Convert placeholders (limit to the lucide grid for speed)
        try {
            createIcons({ icons: lucideIcons, root: lucideGrid });
        } catch (e) {
            // ignore
        }

        btnMoreLucide?.classList.toggle('hidden', filtered.length <= lucideLimit);
    }

    function renderAll() {
        renderFa();
        renderLucide();
    }

    async function boot() {
        // Quick loading message
        faGrid.innerHTML = '<div class="text-sm text-slate-500 p-2">Loading Font Awesome icons…</div>';

        try {
            faItems = await loadFaItems();
        } catch (e) {
            faItems = [];
            faGrid.innerHTML =
                '<div class="text-sm text-red-600 p-2">Font Awesome icon packs are not installed. Run: <code>npm i @fortawesome/free-solid-svg-icons @fortawesome/free-regular-svg-icons @fortawesome/free-brands-svg-icons</code></div>';
            return;
        }

        renderAll();
    }

    // Events
    inpSearch.addEventListener('input', () => renderAll());
    selFaStyle.addEventListener('change', () => renderFa());
    inpSize.addEventListener('change', () => renderAll());
    inpColour.addEventListener('change', () => renderAll());

    btnMoreFa?.addEventListener('click', () => {
        faLimit += 160;
        renderFa();
    });
    btnMoreLucide?.addEventListener('click', () => {
        lucideLimit += 160;
        renderLucide();
    });

    void boot();
}

// Mount on ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
