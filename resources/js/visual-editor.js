import 'grapesjs/dist/css/grapes.min.css';
import grapesjs from 'grapesjs';

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.__VE__ || {};

    // ─── GrapesJS init ───────────────────────────────────────────────────────
    const editor = grapesjs.init({
        container: '#ve-editor',
        height:    '100%',
        width:     'auto',
        storageManager: false,
        components: cfg.html || '',

        // Preserve inline styles (background-image, etc.) exactly as authored.
        avoidInlineStyle: false,
        forceClass:       false,

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

    // ─── Inject CSS into canvas iframe after load ────────────────────────────
    const baseCanvasCSS = `
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; }
        img { max-width: 100%; height: auto; }
    `;

    function injectCanvasCSS() {
        try {
            const iframe = editor.Canvas.getFrameEl();
            const doc    = iframe && iframe.contentDocument;
            if (!doc || !doc.head) return;

            // Remove previous injection if any (e.g. on re-render).
            const prev = doc.getElementById('ve-injected-css');
            if (prev) prev.remove();

            const style = doc.createElement('style');
            style.id = 've-injected-css';
            style.textContent = baseCanvasCSS + '\n' + (cfg.canvasCSS || '');
            doc.head.appendChild(style);
        } catch (e) {
            console.warn('VE: Could not inject canvas CSS', e);
        }
    }

    editor.on('load', injectCanvasCSS);
    editor.on('canvas:frame:load', injectCanvasCSS);

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
