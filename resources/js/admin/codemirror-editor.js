/**
 * CodeMirror 6 editor — replaces admin code textareas with a syntax-highlighted editor.
 *
 * Usage: add data-codemirror="html|css|javascript" to any <textarea>.
 * The original textarea is hidden but stays in the DOM so form submission works normally.
 *
 * For the snippets editor, supply data-codemirror-type-select="<select-id>" to enable
 * live language switching when the type dropdown changes.
 */
import { EditorView, basicSetup } from 'codemirror';
import { EditorState, Compartment } from '@codemirror/state';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';
import { html_beautify, css_beautify, js_beautify } from 'js-beautify';

const BEAUTIFY_HTML_OPTS = {
    indent_size: 4,
    indent_with_tabs: false,
    wrap_line_length: 0,          // don't wrap long lines
    preserve_newlines: true,
    max_preserve_newlines: 2,
    end_with_newline: false,
    extra_liners: [],             // don't add blank lines around block-level tags
    inline: [],                   // treat nothing as inline (so every tag gets its own line)
    content_unformatted: ['style', 'script'],
};

const BEAUTIFY_CSS_OPTS = {
    indent_size: 4,
    indent_with_tabs: false,
    end_with_newline: false,
};

const BEAUTIFY_JS_OPTS = {
    indent_size: 4,
    indent_with_tabs: false,
    end_with_newline: false,
    preserve_newlines: true,
    max_preserve_newlines: 2,
};

function getLang(mode) {
    if (mode === 'css') return css();
    if (mode === 'javascript' || mode === 'script') return javascript();
    return html();
}

function formatCode(code, mode) {
    if (mode === 'css') return css_beautify(code, BEAUTIFY_CSS_OPTS);
    if (mode === 'javascript' || mode === 'script') return js_beautify(code, BEAUTIFY_JS_OPTS);
    return html_beautify(code, BEAUTIFY_HTML_OPTS);
}

function replaceEditorContent(view, formatted) {
    view.dispatch({
        changes: {
            from: 0,
            to: view.state.doc.length,
            insert: formatted,
        },
    });
}

function buildFormatButton(view, getMode) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Format';
    btn.className = 'cm-format-btn';
    btn.title = 'Pretty-print / reformat code';

    btn.addEventListener('click', () => {
        const mode = getMode();
        const current = view.state.doc.toString();
        const formatted = formatCode(current, mode);
        if (formatted !== current) {
            replaceEditorContent(view, formatted);
        }
        btn.textContent = 'Formatted ✓';
        setTimeout(() => { btn.textContent = 'Format'; }, 1500);
    });

    return btn;
}

function initEditor(textarea) {
    const mode = textarea.dataset.codemirror || 'html';
    const langCompartment = new Compartment();

    // Toolbar sits above the editor.
    const toolbar = document.createElement('div');
    toolbar.className = 'cm-toolbar';

    // Wrapper contains toolbar + editor.
    const wrapper = document.createElement('div');
    wrapper.className = 'cm-editor-wrapper';
    wrapper.appendChild(toolbar);
    textarea.parentNode.insertBefore(wrapper, textarea);
    textarea.style.display = 'none';

    const view = new EditorView({
        state: EditorState.create({
            doc: textarea.value,
            extensions: [
                basicSetup,
                oneDark,
                langCompartment.of(getLang(mode)),
                // Keep hidden textarea in sync on every keystroke so form submit works.
                EditorView.updateListener.of(update => {
                    if (update.docChanged) {
                        textarea.value = update.state.doc.toString();
                        // Fire a synthetic input event so any listeners (e.g. icon preview) stay live.
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }),
            ],
        }),
        parent: wrapper,
    });

    // Track the active mode so the format button uses the right formatter
    // (important for snippets where the language can change at runtime).
    let currentMode = mode;
    const formatBtn = buildFormatButton(view, () => currentMode);
    toolbar.appendChild(formatBtn);

    return { view, langCompartment, setMode: (m) => { currentMode = m; } };
}

export function initCodeMirrorEditors() {
    document.querySelectorAll('textarea[data-codemirror]').forEach(textarea => {
        const { view, langCompartment, setMode } = initEditor(textarea);

        // Dynamic language switching — used by the snippets editor.
        const typeSelectId = textarea.dataset.codemirrorTypeSelect;
        if (typeSelectId) {
            const typeEl = document.getElementById(typeSelectId);
            if (typeEl) {
                typeEl.addEventListener('change', () => {
                    const newMode = typeEl.value;
                    setMode(newMode);
                    view.dispatch({
                        effects: langCompartment.reconfigure(getLang(newMode)),
                    });
                });
            }
        }
    });
}
