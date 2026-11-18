import { autoInitPostEditors } from './post-editor.js';

function initEditor() {
    autoInitPostEditors();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEditor);
} else {
    initEditor();
}

document.addEventListener('htmx:afterSwap', initEditor);
window.addEventListener('load', initEditor);

export { autoInitPostEditors };
