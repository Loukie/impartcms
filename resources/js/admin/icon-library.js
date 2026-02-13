// Font Awesome Icon Library (local JSON bundle in resources/js/admin/fa-icon-list.js)
// Renders a searchable, filterable grid.
// - "Copy shortcode" mode: click copies ImpartCMS [icon ...] shortcode
// - "Select" mode (used inside media picker iframe): click selects icon and returns to opener via postMessage

import faIconList from './fa-icon-list';

function qs(sel, root = document) { return root.querySelector(sel); }

function normaliseHexColour(input) {
    const s = String(input || '').trim();
    if (!s) return '#111827';
    if (s[0] === '#') return s.length === 7 ? s : ('#' + s.slice(1).padEnd(6, '0').slice(0, 6));
    return ('#' + s.padEnd(6, '0').slice(0, 6));
}

function toast(root, msg) {
    const el = qs('#impart-icon-toast', root);
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.add('hidden'), 1400);
}

function copyToClipboard(text) {
    try {
        navigator.clipboard.writeText(text);
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

function buildFaShortcode(className, size, colour) {
    const sz = parseInt(size || 24, 10) || 24;
    const col = normaliseHexColour(colour || '#111827');
    return `[icon kind="fa" value="${className}" size="${sz}" colour="${col}"]`;
}

function postSelected(payload) {
    window.parent.postMessage({
        type: 'impartcms-media-selected',
        payload,
    }, window.location.origin);
}

function renderFAIcon(el, className, sizePx, colour) {
    el.innerHTML = '';
    const i = document.createElement('i');
    i.className = className;
    i.style.fontSize = `${sizePx}px`;
    i.style.color = colour;
    i.style.lineHeight = '1';
    el.appendChild(i);
}

function mountFaIconLibrary(root) {
    const grid = qs('#impart-fa-grid', root);
    const inpSearch = qs('#impart-icon-search', root);
    const selStyle = qs('#impart-fa-style', root);
    const inpSize = qs('#impart-icon-size', root);

    const inpColour = qs('#impart-icon-colour', root);
    const inpColourText = qs('#impart-icon-colour-text', root);
    const selAction = qs('#impart-icon-action', root);

    const btnMore = qs('#impart-icon-loadmore-fa', root);

    if (!grid || !inpSearch || !selStyle || !inpSize) return;

    const inIframe = window.self !== window.top;

    // Default mode: page = copy, picker iframe = select
    let currentMode = (root.dataset.mode || '').toLowerCase() || (inIframe ? 'select' : 'copy');
    if (selAction) selAction.value = currentMode;

    // Colour inputs sync
    const syncColourToText = () => {
        if (!inpColour || !inpColourText) return;
        inpColourText.value = inpColour.value;
    };
    const syncTextToColour = () => {
        if (!inpColour || !inpColourText) return;
        const norm = normaliseHexColour(inpColourText.value);
        inpColourText.value = norm;
        inpColour.value = norm;
    };
    if (inpColour && inpColourText) {
        inpColour.addEventListener('input', syncColourToText);
        inpColourText.addEventListener('change', syncTextToColour);
        // initial sync
        syncColourToText();
    }

    if (selAction) {
        selAction.addEventListener('change', () => {
            currentMode = (selAction.value || 'copy').toLowerCase();
            root.dataset.mode = currentMode;
        });
    }

    let page = 0;
    const pageSize = 120;

    function getColour() {
        if (inpColour) return normaliseHexColour(inpColour.value);
        if (inpColourText) return normaliseHexColour(inpColourText.value);
        return '#111827';
    }

    function computeMatches() {
        const q = (inpSearch.value || '').trim().toLowerCase();
        const style = (selStyle.value || 'all').toLowerCase();

        return faIconList.filter(it => {
            const okQ = !q || it.name.toLowerCase().includes(q) || it.search.some(s => s.includes(q));
            const okStyle = (style === 'all') || it.style === style;
            return okQ && okStyle;
        });
    }

    function renderPage(reset = false) {
        const size = parseInt(inpSize.value || '24', 10) || 24;
        const colour = getColour();

        if (reset) {
            page = 0;
            grid.innerHTML = '';
        }

        const matches = computeMatches();
        const start = page * pageSize;
        const end = start + pageSize;
        const slice = matches.slice(start, end);

        slice.forEach(it => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'group text-left border rounded-lg p-3 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500';
            card.setAttribute('data-class', it.className);

            const iconWrap = document.createElement('div');
            iconWrap.className = 'h-12 flex items-center justify-center';
            renderFAIcon(iconWrap, it.className, size, colour);

            const label = document.createElement('div');
            label.className = 'mt-2 text-[11px] text-slate-600 truncate';
            label.textContent = `${it.name} (${it.style})`;

            card.appendChild(iconWrap);
            card.appendChild(label);

            card.addEventListener('click', () => {
                const shortcode = buildFaShortcode(it.className, size, colour);

                // Select mode only really works when embedded in picker iframe.
                if (currentMode === 'select' && inIframe) {
                    postSelected({
                        kind: 'icon',
                        icon: {
                            kind: 'fa',
                            name: it.name,
                            style: it.style,
                            value: it.className,
                            size,
                            colour,
                            shortcode,
                        },
                    });
                    return;
                }

                // Copy mode (default)
                const ok = copyToClipboard(shortcode);
                toast(root, ok ? 'Copied shortcode ✔' : 'Copy failed');
            });

            grid.appendChild(card);
        });

        const more = (end < matches.length);
        if (btnMore) btnMore.classList.toggle('hidden', !more);
    }

    function loadMore() {
        page += 1;
        renderPage(false);
    }

    // Events
    inpSearch.addEventListener('input', () => renderPage(true));
    selStyle.addEventListener('change', () => renderPage(true));
    inpSize.addEventListener('change', () => renderPage(true));
    if (inpColour) inpColour.addEventListener('change', () => renderPage(true));
    if (inpColourText) inpColourText.addEventListener('change', () => renderPage(true));

    if (btnMore) btnMore.addEventListener('click', loadMore);

    // First render
    renderPage(true);
}

document.addEventListener('DOMContentLoaded', () => {
    const root = qs('#impart-icon-library');
    if (root) mountFaIconLibrary(root);
});
