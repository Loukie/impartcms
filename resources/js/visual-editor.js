import 'grapesjs/dist/css/grapes.min.css';
import grapesjs from 'grapesjs';

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.__VE__ || {};

    // ─── Canvas styles ───────────────────────────────────────────────────────
    const baseCanvasCSS = `
      *, *::before, *::after { box-sizing: border-box; }
      body { margin: 0; font-family: system-ui, sans-serif; }
      img { max-width: 100%; height: auto; }
    `;

    const canvasStyles = [baseCanvasCSS];
    if (cfg.canvasCSS && cfg.canvasCSS.trim() !== '') {
        canvasStyles.push(cfg.canvasCSS);
    }

    // ─── GrapesJS init ───────────────────────────────────────────────────────
    const editor = grapesjs.init({
        container: '#ve-editor',
        height:    '100%',
        width:     'auto',
        storageManager: false,
        components: cfg.html || '',
        canvas: {
            styles: canvasStyles,
        },
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
        // Use GrapesJS default panels — no custom panel config needed
        panels: { defaults: [] },
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
