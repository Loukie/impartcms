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

function getLang(mode) {
    if (mode === 'css') return css();
    if (mode === 'javascript' || mode === 'script') return javascript();
    return html();
}

function initEditor(textarea) {
    const mode = textarea.dataset.codemirror || 'html';
    const langCompartment = new Compartment();

    // Wrap the editor in a styled container inserted right before the textarea.
    const wrapper = document.createElement('div');
    wrapper.className = 'cm-editor-wrapper';
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

    return { view, langCompartment };
}

export function initCodeMirrorEditors() {
    document.querySelectorAll('textarea[data-codemirror]').forEach(textarea => {
        const { view, langCompartment } = initEditor(textarea);

        // Dynamic language switching — used by the snippets editor.
        const typeSelectId = textarea.dataset.codemirrorTypeSelect;
        if (typeSelectId) {
            const typeEl = document.getElementById(typeSelectId);
            if (typeEl) {
                typeEl.addEventListener('change', () => {
                    view.dispatch({
                        effects: langCompartment.reconfigure(getLang(typeEl.value)),
                    });
                });
            }
        }
    });
}
