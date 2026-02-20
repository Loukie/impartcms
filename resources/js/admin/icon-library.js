// Font Awesome Icon Library
// - Copy mode: click card copies ImpartCMS [icon ...] shortcode (portable: embeds SVG)
// - Select mode (picker iframe): click card selects icon and returns to opener via postMessage

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

function buildFaShortcode(icon, size, colour) {
    const sz = parseInt(size || 24, 10) || 24;
    const col = normaliseHexColour(colour || '#111827');

    // Compact shortcode (recommended): human-readable and still supported by CMS.
    // Example: [icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]
    const value = String(icon && (icon.className || icon.value) ? (icon.className || icon.value) : '').trim();
    if (!value) return '';

    return `[icon kind="fa" value="${value}" size="${sz}" colour="${col}"]`;
}


function postSelected(payload) {
    window.parent.postMessage({
        type: 'impart-media-selected',
        payload,
    }, window.location.origin);
}

function renderFASvg(el, svg, sizePx, colour) {
    el.innerHTML = '';
    if (!svg || typeof svg !== 'string' || !svg.trim().startsWith('<svg')) return false;

    el.innerHTML = svg;
    const s = el.querySelector('svg');
    if (!s) {
        el.innerHTML = '';
        return false;
    }

    // Make SVG size/colour consistent.
    s.removeAttribute('width');
    s.removeAttribute('height');
    s.setAttribute('aria-hidden', 'true');
    s.setAttribute('focusable', 'false');
    s.style.width = `${sizePx}px`;
    s.style.height = `${sizePx}px`;
    s.style.display = 'block';
    s.style.color = colour;

    return true;
}

function renderFAIcon(el, icon, sizePx, colour) {
    // Prefer SVG (works everywhere). Fallback to <i> for safety.
    const ok = renderFASvg(el, icon.svg, sizePx, colour);
    if (ok) return;

    el.innerHTML = '';
    const i = document.createElement('i');
    i.className = icon.className;
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
        const qRaw = (inpSearch.value || '').trim().toLowerCase();
        const style = (selStyle.value || 'all').toLowerCase();
        const terms = qRaw ? qRaw.split(/\s+/).filter(Boolean) : [];

        return faIconList.filter(it => {
            const name = String(it.name || '').toLowerCase();
            const cls = String(it.className || it.value || '').toLowerCase();
            const label = String(it.label || '').toLowerCase();
            const st = String(it.style || '').toLowerCase();

            const hay = `${name} ${label} ${cls} ${st}`.trim();

            const okQ = terms.length === 0 || terms.every(t => hay.includes(t));
            const okStyle = (style === 'all') || st === style;

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
            const card = document.createElement('div');
            card.className = 'relative group text-left border rounded-lg p-3 hover:bg-slate-50 focus-within:ring-2 focus-within:ring-indigo-500';
            card.setAttribute('data-class', it.className);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left focus:outline-none';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'h-12 flex items-center justify-center';
            renderFAIcon(iconWrap, it, size, colour);

            const label = document.createElement('div');
            label.className = 'mt-2 text-[11px] text-slate-600 truncate';
            label.textContent = `${it.name} (${it.style})`;

            btn.appendChild(iconWrap);
            btn.appendChild(label);

            // Copy button (always available)
            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'absolute top-2 right-2 px-2 py-1 rounded-md border bg-white text-[11px] font-semibold text-slate-700 hover:bg-slate-50 shadow-sm';
            copyBtn.textContent = 'Copy';

            copyBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();

                const shortcode = buildFaShortcode(it, size, colour);
                const ok = await copyToClipboard(shortcode);
                toast(root, ok ? 'Copied shortcode ✔' : 'Copy failed');
            });

            btn.addEventListener('click', async () => {
                const shortcode = buildFaShortcode(it, size, colour);

                // Select mode only really works when embedded in picker iframe.
                if (currentMode === 'select' && inIframe) {
                    postSelected({
                        kind: 'icon',
                        icon: {
                            kind: 'fa',
                            name: it.name,
                            style: it.style,
                            value: it.className,
                            svg: it.svg || '',
                            size,
                            colour,
                            shortcode,
                        },
                    });
                    return;
                }

                // Copy mode (default)
                const ok = await copyToClipboard(shortcode);
                toast(root, ok ? 'Copied shortcode ✔' : 'Copy failed');
            });

            card.appendChild(btn);
            card.appendChild(copyBtn);
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

function bootImpartFaIconLibrary() {
    const root = qs('#impart-icon-library');
    if (root) mountFaIconLibrary(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootImpartFaIconLibrary);
} else {
    bootImpartFaIconLibrary();
}
