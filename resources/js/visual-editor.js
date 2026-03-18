import 'grapesjs/dist/css/grapes.min.css';
import grapesjs from 'grapesjs';

const cfg = window.__VE__ || {};

// ─── Canvas styles ───────────────────────────────────────────────────────────
// Inject a comfortable base + any page-specific CSS so elements render
// correctly inside the editor iframe.
const baseCanvasCSS = `
  *, *::before, *::after { box-sizing: border-box; }
  body { margin: 0; font-family: system-ui, sans-serif; }
  img { max-width: 100%; height: auto; }
`;

const canvasStyles = [baseCanvasCSS];
if (cfg.canvasCSS && cfg.canvasCSS.trim() !== '') {
    canvasStyles.push(cfg.canvasCSS);
}

// ─── GrapesJS init ───────────────────────────────────────────────────────────
const editor = grapesjs.init({
    container: '#ve-editor',
    height:    '100%',
    width:     'auto',

    // Don't store anything internally — we manage persistence ourselves.
    storageManager: false,

    // Load initial HTML.
    components: cfg.html || '',

    // Inject canvas CSS so page styles are visible while editing.
    canvas: {
        styles: canvasStyles,
    },

    // Asset manager wired to the media library.
    assetManager: {
        autoAdd:  true,
        upload:   false,        // uploads go through the media library, not GrapesJS
        assets:   [],           // populated on open via remote fetch
        params:   {},
        headers:  { 'X-CSRF-TOKEN': cfg.csrfToken },
        custom: {
            // Called when the asset manager panel opens — fetch fresh from media library.
            open(props) {
                fetch(cfg.assetsUrl, {
                    headers: { 'Accept': 'application/json' },
                })
                .then(r => r.json())
                .then(json => {
                    const assets = (json.data || []).map(a => ({
                        type:   'image',
                        src:    a.src,
                        name:   a.name || '',
                        width:  a.width  || 0,
                        height: a.height || 0,
                    }));
                    props.am.add(assets);
                    props.open();
                })
                .catch(() => props.open());
            },
            // Let GrapesJS handle its own close behaviour.
            close(props) { props.close(); },
        },
    },

    // Panel layout — keep it simple with just what's needed.
    panels: {
        defaults: [
            {
                id: 'layers',
                el: '.panel__right',
                resizable: {
                    maxDim: 350,
                    minDim: 200,
                    tc: false,
                    cl: true,
                    cr: false,
                    bc: false,
                    keyWidth: 'flex-basis',
                },
            },
            {
                id: 'panel-switcher',
                el: '.panel__switcher',
                buttons: [
                    {
                        id: 'show-layers',
                        active: true,
                        label: 'Layers',
                        command: 'show-layers',
                        togglable: false,
                    },
                    {
                        id: 'show-style',
                        label: 'Styles',
                        command: 'show-styles',
                        togglable: false,
                    },
                    {
                        id: 'show-traits',
                        label: 'Traits',
                        command: 'show-traits',
                        togglable: false,
                    },
                ],
            },
        ],
    },
});

// ─── Panel commands ───────────────────────────────────────────────────────────
editor.Commands.add('show-layers', {
    getRowEl(ed) { return ed.getContainer().closest('body').querySelector('.panel__right'); },
    getLayersEl(row) { return row.querySelector('.layers-container'); },
    run(ed, sender) {
        const lm = ed.LayerManager;
        const row = this.getRowEl(ed);
        const layers = this.getLayersEl(row);
        if (layers) layers.style.display = '';
        if (lm) lm.render();
    },
    stop(ed, sender) {
        const row = this.getRowEl(ed);
        const layers = this.getLayersEl(row);
        if (layers) layers.style.display = 'none';
    },
});

editor.Commands.add('show-styles', {
    run(ed) { ed.StyleManager && ed.StyleManager.render(); },
});

editor.Commands.add('show-traits', {
    run(ed) { ed.TraitManager && ed.TraitManager.render(); },
});

// ─── Save ────────────────────────────────────────────────────────────────────
const saveBtn    = document.getElementById('ve-save');
const statusEl   = document.getElementById('ve-status');

function setStatus(msg, colour) {
    if (!statusEl) return;
    statusEl.textContent = msg;
    statusEl.style.color = colour || '#64748b';
}

async function save() {
    if (!saveBtn) return;
    saveBtn.disabled = true;
    saveBtn.classList.add('saving');
    saveBtn.textContent = 'Saving…';
    setStatus('', '');

    // Get the inner HTML of the <body> from the canvas iframe.
    const html = editor.getHtml();

    try {
        const res = await fetch(cfg.saveUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': cfg.csrfToken,
            },
            body: JSON.stringify({ html }),
        });

        if (!res.ok) throw new Error('Server returned ' + res.status);
        setStatus('Saved', '#22c55e');
    } catch (err) {
        setStatus('Save failed', '#ef4444');
        console.error('Visual editor save error:', err);
    } finally {
        saveBtn.disabled = false;
        saveBtn.classList.remove('saving');
        saveBtn.textContent = 'Save';
    }
}

if (saveBtn) {
    saveBtn.addEventListener('click', save);
}

// Ctrl/Cmd + S shortcut.
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        save();
    }
});
