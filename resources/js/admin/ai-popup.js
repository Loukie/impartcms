/*
 * Admin AI helper popup
 * - Works across admin
 * - Lets user select a Page and submit a change request
 * - Calls backend endpoints:
 *   - GET  /admin/ai/pages/search?q=...
 *   - POST /admin/ai/page-assist
 */

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function qs(id) {
    return document.getElementById(id);
}

function safeJson(res) {
    return res.json().catch(() => ({}));
}

function findPageContext() {
    const el = document.getElementById('impart-ai-page-context');
    if (!el) return null;
    const pageId = el.getAttribute('data-page-id');
    const title = el.getAttribute('data-page-title') || '';
    const slug = el.getAttribute('data-page-slug') || '';
    if (!pageId) return null;
    return {
        id: String(pageId),
        title,
        slug,
    };
}

async function searchPages(q) {
    const root = document.getElementById('impart-ai-assist');
    const adminBase = root ? (root.getAttribute('data-admin-base') || '/admin') : '/admin';
    const url = new URL(adminBase.replace(/\/$/, '') + '/ai/pages/search', window.location.origin);
    if (q && q.trim() !== '') url.searchParams.set('q', q.trim());

    const res = await fetch(url.toString(), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!res.ok) throw new Error('Search failed');
    const json = await safeJson(res);
    return Array.isArray(json.pages) ? json.pages : [];
}

function fillSelect(selectEl, pages, selectedId = '') {
    // Keep the first option
    const keep = selectEl.querySelector('option[value=""]');
    selectEl.innerHTML = '';
    if (keep) selectEl.appendChild(keep);
    else {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '— Select a page —';
        selectEl.appendChild(opt);
    }

    for (const p of pages) {
        const opt = document.createElement('option');
        opt.value = String(p.id);
        const status = (p.status || '').toUpperCase();
        opt.textContent = `${p.title} (${p.slug})${status ? ' · ' + status : ''}`;
        if (String(p.id) === String(selectedId)) opt.selected = true;
        selectEl.appendChild(opt);
    }
}

function setVisible(el, yes) {
    if (!el) return;
    el.classList.toggle('hidden', !yes);
}

function setText(el, txt) {
    if (!el) return;
    el.textContent = txt;
}

function setHtml(el, html) {
    if (!el) return;
    el.innerHTML = html;
}

function getBodyTextarea() {
    // Only present on page edit screen
    return document.querySelector('textarea[name="body"]');
}

export default (function initAdminAiPopup() {
    const btn = qs('impart-ai-assist-btn');
    const modal = qs('impart-ai-assist-modal');
    if (!btn || !modal) return;

    const root = document.getElementById('impart-ai-assist');
    const adminBase = root ? (root.getAttribute('data-admin-base') || '/admin') : '/admin';

    const closeEls = modal.querySelectorAll('[data-impart-ai-close]');
    const subtitle = qs('impart-ai-assist-subtitle');
    const searchInput = qs('impart-ai-page-search');
    const select = qs('impart-ai-page-select');
    const instruction = qs('impart-ai-instruction');
    const mode = qs('impart-ai-mode');
    const saveAs = qs('impart-ai-save');
    const runBtn = qs('impart-ai-run');
    const errBox = qs('impart-ai-error');
    const okBox = qs('impart-ai-success');
    const hint = qs('impart-ai-hint');

    let lastSearchTimer = null;
    let currentPages = [];

    function open() {
        setVisible(modal, true);
        document.documentElement.style.overflow = 'hidden';
        setVisible(errBox, false);
        setVisible(okBox, false);
        setText(hint, '');

        // Auto-select when editing a page
        const ctx = findPageContext();
        if (ctx) {
            setText(subtitle, `Editing: ${ctx.title} (${ctx.slug})`);
            // Load options with ctx selected
            loadPages('', ctx.id).catch(() => {});
        } else {
            setText(subtitle, 'Pick a page and describe what you want changed.');
            loadPages('').catch(() => {});
        }
    }

    function close() {
        setVisible(modal, false);
        document.documentElement.style.overflow = '';
    }

    async function loadPages(q, selectedId = '') {
        const pages = await searchPages(q);
        currentPages = pages;
        fillSelect(select, pages, selectedId);
    }

    async function run() {
        setVisible(errBox, false);
        setVisible(okBox, false);

        const pageId = select.value;
        const text = (instruction.value || '').trim();
        if (!pageId) {
            setText(errBox, 'Please select a page.');
            setVisible(errBox, true);
            return;
        }
        if (!text) {
            setText(errBox, 'Please enter an instruction.');
            setVisible(errBox, true);
            return;
        }

        runBtn.disabled = true;
        runBtn.textContent = 'Working…';

        try {
            const url = adminBase.replace(/\/$/, '') + '/ai/page-assist';
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    page_id: pageId,
                    instruction: text,
                    mode: mode.value,
                    save_as: saveAs.value,
                }),
            });

            const json = await safeJson(res);
            if (!res.ok || !json || json.ok !== true) {
                const msg = (json && (json.error || json.message)) ? (json.error || json.message) : 'AI request failed.';
                throw new Error(msg);
            }

            // If we are on the edit page for this page, sync the textarea.
            const ctx = findPageContext();
            const bodyTa = getBodyTextarea();
            if (ctx && bodyTa && String(ctx.id) === String(pageId)) {
                bodyTa.value = json.clean_html || '';
                setText(hint, 'Updated the Body editor. Remember to click Save Draft / Go Live if you want to persist other changes.');
            } else {
                setText(hint, 'Saved to the page. You can open it to review.');
            }

            const link = json.edit_url ? `<a class="underline" href="${json.edit_url}">Open page</a>` : '';
            setHtml(okBox, `✅ ${json.message || 'Done.'} ${link}`);
            setVisible(okBox, true);
        } catch (e) {
            setText(errBox, e && e.message ? e.message : 'AI request failed.');
            setVisible(errBox, true);
        } finally {
            runBtn.disabled = false;
            runBtn.textContent = 'Generate';
        }
    }

    btn.addEventListener('click', open);
    closeEls.forEach((el) => el.addEventListener('click', close));

    modal.addEventListener('click', (e) => {
        const t = e.target;
        if (t && t.hasAttribute('data-impart-ai-close')) close();
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    // Debounced search
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (lastSearchTimer) clearTimeout(lastSearchTimer);
            lastSearchTimer = setTimeout(() => {
                loadPages(searchInput.value, select.value || '').catch(() => {});
            }, 250);
        });
    }

    runBtn.addEventListener('click', run);
})();
