import 'grapesjs/dist/css/grapes.min.css';
import grapesjs            from 'grapesjs';
import grapesjsBlocksBasic from 'grapesjs-blocks-basic';
import grapesjsForms       from 'grapesjs-plugin-forms';
import grapesjsCustomCode  from 'grapesjs-custom-code';
import grapesjsTabs        from 'grapesjs-tabs';
import grapesjsTooltip     from 'grapesjs-tooltip';
import grapesjsTyped       from 'grapesjs-typed';

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.__VE__ || {};

    const baseCanvasCSS = `
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; }
        img { max-width: 100%; height: auto; }
    `;

    // ─── GrapesJS init ───────────────────────────────────────────────────────
    const editor = grapesjs.init({
        container: '#ve-editor',
        height:    '100%',
        width:     'auto',
        storageManager: false,
        components: cfg.html || '',

        // protectedCss is injected directly into the canvas iframe <style> tag.
        // This is the correct GrapesJS API for injecting non-editable CSS.
        protectedCss: baseCanvasCSS + '\n' + (cfg.canvasCSS || ''),

        // Preserve inline styles (background-image, etc.) exactly as authored.
        avoidInlineStyle: false,
        forceClass:       false,

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

    // ─── Extra blocks ────────────────────────────────────────────────────────
    const bm = editor.BlockManager;

    bm.add('heading', {
        label: 'Heading',
        category: 'Basic',
        content: '<h2>Your Heading Here</h2>',
        media: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h7"/></svg>`,
    });

    bm.add('button', {
        label: 'Button',
        category: 'Basic',
        content: '<a href="#" style="display:inline-block;padding:12px 28px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">Click Me</a>',
        media: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="3"/><path d="M9 12h6"/></svg>`,
    });

    bm.add('divider', {
        label: 'Divider',
        category: 'Basic',
        content: '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">',
        media: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>`,
    });

    bm.add('quote', {
        label: 'Quote',
        category: 'Basic',
        content: '<blockquote style="border-left:4px solid #2563eb;padding:12px 20px;margin:0;font-style:italic;color:#374151;">"Your quote text here."</blockquote>',
        media: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.75-2.5-2-3H4c-1.25.5-2 1.75-2 3v4c0 1.25.75 2.5 2 3l1 1M21 21c-3 0-7-1-7-8V5c0-1.25.75-2.5 2-3h4c1.25.5 2 1.75 2 3v4c0 1.25-.75 2.5-2 3l-1 1"/></svg>`,
    });

    bm.add('section', {
        label: 'Section',
        category: 'Layout',
        content: '<section style="padding:60px 20px;"><div style="max-width:1200px;margin:0 auto;"><h2>Section Title</h2><p>Section content goes here.</p></div></section>',
        media: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20"/></svg>`,
    });

    bm.add('hero', {
        label: 'Hero',
        category: 'Layout',
        content: '<section style="padding:100px 20px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;text-align:center;"><h1 style="font-size:2.5rem;margin-bottom:16px;">Hero Title</h1><p style="font-size:1.1rem;margin-bottom:32px;opacity:.9;">Supporting subtitle text goes here.</p><a href="#" style="display:inline-block;padding:14px 32px;background:#fff;color:#2563eb;border-radius:6px;font-weight:700;text-decoration:none;">Get Started</a></section>',
        media: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>`,
    });


    // ─── Save ────────────────────────────────────────────────────────────────
    const saveBtn  = document.getElementById('ve-save');
    const statusEl = document.getElementById('ve-status');

    function setStatus(msg, colour) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.style.color = colour || '#64748b';
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
                body: JSON.stringify({ html: editor.getHtml(), extracted_css: cfg.extractedCSS || '' }),
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

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            save();
        }
    });
});
