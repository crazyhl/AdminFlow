import hljs, { highlightAllCodeBlocks } from './highlight.js';

window.hljs = hljs;

function initPreview() {
    highlightAllCodeBlocks();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPreview);
} else {
    initPreview();
}

document.addEventListener('htmx:afterSwap', initPreview);
window.addEventListener('load', initPreview);

export { highlightAllCodeBlocks };
