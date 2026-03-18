import 'grapesjs/dist/css/grapes.min.css';
import grapesjs             from 'grapesjs';
import grapesjsBlocksBasic  from 'grapesjs-blocks-basic';
import grapesjsForms        from 'grapesjs-plugin-forms';
import grapesjsCustomCode   from 'grapesjs-custom-code';
import grapesjsTabs         from 'grapesjs-tabs';
import grapesjsTooltip      from 'grapesjs-tooltip';
import grapesjsTyped        from 'grapesjs-typed';

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.__VE__ || {};

    const baseCanvasCSS = `
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; }
        img { max-width: 100%; height: auto; }
    `;

    // Override scroll-reveal animations — no IntersectionObserver runs in the
    // canvas iframe, so elements with opacity:0 would stay hidden forever.
    const editorOverrideCSS = `
        .reveal, [class*="reveal-"] {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    `;

    // ─── Extract @imports from page CSS before editor init ───────────────────
    // @import rules must come first in any stylesheet; non-@import page CSS is
    // loaded into the CSS manager (not protectedCss) so the Style Manager panel
    // can display and edit existing class values (e.g. .split-image height).
    const cssImportLines = [];
    const canvasCssBody  = (cfg.canvasCSS || '').replace(
        /@import\s+url\(['"][^'"]*['"]\)\s*;/gi,
        m => { cssImportLines.push(m); return ''; }
    ).trim();

    // ─── GrapesJS init ───────────────────────────────────────────────────────
    const editor = grapesjs.init({
        container: '#ve-editor',
        height:    '100%',
        width:     'auto',
        storageManager: false,
        components: cfg.html || '',

        // protectedCss = @imports (for fonts) + base reset + reveal override.
        // The page-specific CSS body goes into the CSS manager instead (see below),
        // so it appears in the Style Manager when a classed element is selected.
        protectedCss: cssImportLines.join('\n') + '\n' + baseCanvasCSS + '\n' + editorOverrideCSS,
        avoidInlineStyle: false,
        forceClass:       false,

        deviceManager: {
            devices: [
                { name: 'Desktop', width: ''      },
                { name: 'Tablet',  width: '768px', widthMedia: '992px' },
                { name: 'Mobile',  width: '375px', widthMedia: '480px' },
            ],
        },

        plugins: [
            grapesjsBlocksBasic,
            grapesjsForms,
            grapesjsCustomCode,
            grapesjsTabs,
            grapesjsTooltip,
            grapesjsTyped,
        ],
        pluginsOpts: {
            [grapesjsBlocksBasic]: { flexGrid: true },
            [grapesjsForms]:       {},
            [grapesjsCustomCode]:  {},
            [grapesjsTabs]:        {},
            [grapesjsTooltip]:     {},
            [grapesjsTyped]:       {},
        },

        canvas: {},
        assetManager: {
            autoAdd: true,
            upload:  false,
            assets:  [],
            custom: {
                open(props) {
                    fetch(cfg.assetsUrl, { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(json => {
                            const assets = (json.data || []).map(a => ({
                                type: 'image', src: a.src, name: a.name || '',
                                width: a.width || 0, height: a.height || 0,
                            }));
                            props.am.add(assets);
                            props.open();
                        })
                        .catch(() => props.open());
                },
                close(props) { props.close(); },
            },
        },
    });

    // ─── Load page CSS into CSS manager ──────────────────────────────────────
    // After GrapesJS finishes loading, inject the page's CSS into the CSS manager
    // (not just protectedCss). This makes the Style Manager right panel show
    // existing property values — e.g. selecting .split-image shows its height.
    editor.on('load', () => {
        if (canvasCssBody.trim()) {
            editor.setStyle(canvasCssBody);
        }
    });

    // ─── Extra blocks ────────────────────────────────────────────────────────
    const ic = path =>
        `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;

    const bm = editor.BlockManager;

    bm.add('heading', {
        label: 'Heading', category: 'Basic',
        content: '<h2>Your Heading Here</h2>',
        media: ic('<path d="M4 6h16M4 12h10M4 18h7"/>'),
    });
    bm.add('ve-button', {
        label: 'Button', category: 'Basic',
        content: '<a href="#" style="display:inline-block;padding:12px 28px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">Click Me</a>',
        media: ic('<rect x="3" y="8" width="18" height="8" rx="3"/><path d="M9 12h6"/>'),
    });
    bm.add('divider', {
        label: 'Divider', category: 'Basic',
        content: '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">',
        media: ic('<path d="M5 12h14"/>'),
    });
    bm.add('section', {
        label: 'Section', category: 'Layout',
        content: '<section style="padding:60px 20px;"><div style="max-width:1200px;margin:0 auto;"><h2>Section Title</h2><p>Section content goes here.</p></div></section>',
        media: ic('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20"/>'),
    });
    bm.add('hero', {
        label: 'Hero', category: 'Layout',
        content: '<section style="padding:100px 20px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;text-align:center;"><h1 style="font-size:2.5rem;margin-bottom:16px;">Hero Title</h1><p style="font-size:1.1rem;margin-bottom:32px;opacity:.9;">Supporting subtitle text goes here.</p><a href="#" style="display:inline-block;padding:14px 32px;background:#fff;color:#2563eb;border-radius:6px;font-weight:700;text-decoration:none;">Get Started</a></section>',
        media: ic('<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>'),
    });

    // ─── Save ────────────────────────────────────────────────────────────────
    const saveBtn  = document.getElementById('ve-save');
    const statusEl = document.getElementById('ve-status');

    function setStatus(msg, colour) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.style.color = colour || '#64748b';
    }

    // Return only the editable page-body HTML, excluding any nav/footer wrappers
    // that were injected for visual context (they are marked selectable:false/hoverable:false).
    // GrapesJS strips HTML comments so we cannot rely on <!-- ve-body-start --> markers —
    // instead we read the component tree directly.
    function getCleanHtml() {
        const allComponents = editor.getComponents();
        const bodyParts = [];
        let hasLayoutWrappers = false;

        allComponents.each(comp => {
            if (comp.get('selectable') === false || comp.get('hoverable') === false) {
                // This is a read-only nav or footer wrapper — skip it.
                hasLayoutWrappers = true;
            } else {
                bodyParts.push(comp.toHTML());
            }
        });

        if (hasLayoutWrappers) {
            // Return only the editable body components.
            return bodyParts.join('\n').replace(/<\/?(html|head|body)[^>]*>/gi, '').trim();
        }

        // No layout wrappers present (e.g. editing a layout block) — use full output.
        const raw = editor.getHtml();
        const match = raw.match(/<body[^>]*>([\s\S]*)<\/body>/i);
        const inner = match ? match[1] : raw;
        return inner.replace(/<\/?(html|head|body)[^>]*>/gi, '').trim();
    }

    async function save() {
        if (!saveBtn) return;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
        setStatus('', '');
        try {
            const res = await fetch(cfg.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                },
                body: JSON.stringify({
                    html:          getCleanHtml(),
                    extracted_css: cfg.extractedCSS || '',
                    // full_css = @imports (for fonts) + everything in the CSS manager
                    // (original page CSS + any Style Manager edits). The server uses
                    // this to REPLACE the snippet content so edited values are persisted.
                    full_css: (
                        cssImportLines.join('\n') +
                        (cssImportLines.length ? '\n\n' : '') +
                        (editor.getCss() || '')
                    ).trim(),
                }),
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            setStatus('Saved', '#22c55e');
        } catch (err) {
            setStatus('Save failed', '#ef4444');
            console.error('Visual editor save error:', err);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    }

    saveBtn && saveBtn.addEventListener('click', save);
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); save(); }
    });
});
