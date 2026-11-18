/**
 * AdminFlow CMS Markdown 编辑器独立的 Vite 驱动模块
 * 按容器 ([data-post-editor]) 挂载，快捷键绑定当前表单，避免向 window 重复注册
 */
import hljs, { highlightAllCodeBlocks } from './highlight.js';

class HistoryManager {
    constructor(textarea) {
        this.textarea = textarea;
        this.undoStack = [];
        this.redoStack = [];
        this.saveTimer = null;
        this.isApplying = false;
        this.pushState();
        this.bindEvents();
    }

    pushState() {
        if (this.saveTimer) {
            clearTimeout(this.saveTimer);
            this.saveTimer = null;
        }
        const val = this.textarea.value;
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        if (this.undoStack.length > 0) {
            const last = this.undoStack[this.undoStack.length - 1];
            if (last.value === val) return;
        }
        this.undoStack.push({ value: val, start, end });
        this.redoStack = [];
        if (this.undoStack.length > 100) this.undoStack.shift();
    }

    flush() {
        if (this.saveTimer) {
            this.pushState();
        }
    }

    undo() {
        if (this.undoStack.length <= 1) return;
        this.isApplying = true;
        try {
            const current = this.undoStack.pop();
            this.redoStack.push(current);
            const prev = this.undoStack[this.undoStack.length - 1];
            this.applyState(prev);
            this.textarea.dispatchEvent(new Event('input'));
        } finally {
            this.isApplying = false;
        }
    }

    redo() {
        if (this.redoStack.length === 0) return;
        this.isApplying = true;
        try {
            const next = this.redoStack.pop();
            this.undoStack.push(next);
            this.applyState(next);
            this.textarea.dispatchEvent(new Event('input'));
        } finally {
            this.isApplying = false;
        }
    }

    applyState(state) {
        this.textarea.value = state.value;
        this.textarea.setSelectionRange(state.start, state.end);
    }

    bindEvents() {
        this.textarea.addEventListener('input', (e) => {
            if (this.isApplying || !e.isTrusted) return;
            this.redoStack = [];
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                this.pushState();
            }, 300);
        });

        this.textarea.addEventListener('keydown', (e) => {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const cmd = isMac ? e.metaKey : e.ctrlKey;
            if (cmd && !e.shiftKey && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                this.flush();
                this.undo();
            } else if ((cmd && e.shiftKey && e.key.toLowerCase() === 'z') || (cmd && e.key.toLowerCase() === 'y')) {
                e.preventDefault();
                this.flush();
                this.redo();
            }
        });
    }
}

export function initPostEditor(wrapper) {
    if (!wrapper || wrapper.__postEditorInitialized) return;
    wrapper.__postEditorInitialized = true;

    const textarea = wrapper.querySelector('#markdown-textarea');
    if (!textarea) return;

    const preview = wrapper.querySelector('#markdown-preview');
    const wordCount = wrapper.querySelector('#word-count');
    const editorGrid = wrapper.querySelector('#editor-grid');
    const editorPaneLeft = wrapper.querySelector('#editor-pane-left');
    const editorPaneRight = wrapper.querySelector('#editor-pane-right');
    const dropOverlay = wrapper.querySelector('#drop-overlay');
    const form = wrapper.closest('form') || document.getElementById('post-form');
    const previewUrl = wrapper.dataset.previewUrl;

    const historyManager = new HistoryManager(textarea);

    function applyCodeEnhancements() {
        if (!preview) return;
        highlightAllCodeBlocks(preview);

        preview.querySelectorAll('pre').forEach((pre) => {
            if (pre.querySelector('.btn-copy-code')) return;
            pre.classList.add('relative', 'group');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-copy-code absolute top-3 right-3 px-2 py-0.5 rounded text-[10px] bg-stone-800/90 text-stone-300 opacity-0 group-hover:opacity-100 transition-opacity z-10';
            btn.innerText = 'Copy';
            btn.onclick = () => {
                const code = pre.querySelector('code')?.innerText || '';
                navigator.clipboard.writeText(code).then(() => {
                    btn.innerText = 'Copied!';
                    setTimeout(() => (btn.innerText = 'Copy'), 1500);
                });
            };
            pre.appendChild(btn);
        });
    }

    let currentPreviewReq = 0;
    let previewAbortController = null;

    async function renderLivePreview() {
        const reqId = ++currentPreviewReq;
        if (previewAbortController) {
            previewAbortController.abort();
            previewAbortController = null;
        }

        const rawText = textarea.value;
        const len = rawText.trim().length;
        if (wordCount) {
            wordCount.innerText = `${len} 字`;
        }

        if (!rawText.trim()) {
            if (preview) {
                preview.innerHTML = '<p class="text-stone-400 italic">预览区域就绪，请在左侧撰写内容...</p>';
            }
            return;
        }

        if (!previewUrl) return;

        const abortCtrl = new AbortController();
        previewAbortController = abortCtrl;

        try {
            const formData = new FormData();
            formData.append('body_markdown', rawText);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                formData.append('_token', token);
            }

            const res = await fetch(previewUrl, {
                method: 'POST',
                body: formData,
                signal: abortCtrl.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (reqId !== currentPreviewReq) {
                return;
            }

            if (res.ok) {
                const safeHtml = await res.text();
                if (preview) {
                    preview.innerHTML = safeHtml;
                    applyCodeEnhancements();
                }
            }
        } catch (e) {
            if (e.name === 'AbortError') return;
            console.error('Markdown 权威安全渲染请求异常:', e);
        }
    }

    let renderTimer = null;
    textarea.addEventListener('input', () => {
        clearTimeout(renderTimer);
        renderTimer = setTimeout(renderLivePreview, 150);
    });

    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            e.preventDefault();
            historyManager.pushState();
            if (!document.execCommand('insertText', false, '  ')) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.setRangeText('  ', start, end, 'end');
            }
            historyManager.pushState();
            renderLivePreview();
        }
    });

    // 快捷键 Cmd+S / Ctrl+S 绑定到当前表单，杜绝向 window 重复挂载导致多次执行
    if (form) {
        form.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                e.stopPropagation();
                form.requestSubmit();
            }
        });

        // 监听表单异步请求状态切换保存按钮 loading 指示器
        const saveSpinner = document.getElementById('save-spinner');
        const saveIcon = document.getElementById('save-icon');
        const saveText = document.getElementById('save-text');

        form.addEventListener('htmx:beforeRequest', () => {
            historyManager.flush();
            saveSpinner?.classList.remove('hidden');
            saveIcon?.classList.add('hidden');
            if (saveText) saveText.innerText = '保存中...';
        });
        form.addEventListener('htmx:afterRequest', () => {
            saveSpinner?.classList.add('hidden');
            saveIcon?.classList.remove('hidden');
            if (saveText) saveText.innerText = '保存内容';
        });
    }

    // 左右双栏滚动同步
    if (editorPaneRight) {
        let isSyncingLeft = false;
        let isSyncingRight = false;
        textarea.addEventListener('scroll', () => {
            if (isSyncingRight) return;
            isSyncingLeft = true;
            const percentage = textarea.scrollTop / (textarea.scrollHeight - textarea.clientHeight);
            editorPaneRight.scrollTop = percentage * (editorPaneRight.scrollHeight - editorPaneRight.clientHeight);
            setTimeout(() => (isSyncingLeft = false), 50);
        });

        editorPaneRight.addEventListener('scroll', () => {
            if (isSyncingLeft) return;
            isSyncingRight = true;
            const percentage = editorPaneRight.scrollTop / (editorPaneRight.scrollHeight - editorPaneRight.clientHeight);
            textarea.scrollTop = percentage * (textarea.scrollHeight - textarea.clientHeight);
            setTimeout(() => (isSyncingRight = false), 50);
        });
    }

    function insertSyntax(prefix, suffix) {
        historyManager.pushState();
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const replacement = prefix + selectedText + suffix;

        if (!document.execCommand('insertText', false, replacement)) {
            textarea.setRangeText(replacement, start, end, 'end');
        }

        textarea.focus();
        if (selectedText.length > 0) {
            textarea.selectionStart = start + prefix.length;
            textarea.selectionEnd = start + prefix.length + selectedText.length;
        } else {
            textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
        }
        historyManager.pushState();
        renderLivePreview();
    }

    function insertTable() {
        const tableTemplate = "\n| 标题 1 | 标题 2 | 标题 3 |\n| :--- | :--- | :--- |\n| 单元格 | 单元格 | 单元格 |\n";
        insertSyntax(tableTemplate, "");
    }

    function switchMode(mode) {
        const btnSplit = document.getElementById('btn-mode-split');
        const btnEdit = document.getElementById('btn-mode-edit');
        const btnPreview = document.getElementById('btn-mode-preview');

        [btnSplit, btnEdit, btnPreview].forEach((b) => {
            if (b) {
                b.className = 'px-2 py-0.5 rounded-md font-medium text-stone-500 hover:text-stone-900 dark:hover:text-stone-100';
            }
        });

        const activeClass = 'px-2 py-0.5 rounded-md font-medium bg-white dark:bg-[#191919] text-stone-900 dark:text-stone-100 shadow-2xs';

        if (mode === 'split') {
            if (btnSplit) btnSplit.className = activeClass;
            if (editorGrid) editorGrid.className = 'grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-stone-200/70 dark:divide-stone-800 flex-1 min-h-[580px]';
            if (editorPaneLeft) editorPaneLeft.style.display = 'flex';
            if (editorPaneRight) editorPaneRight.style.display = 'flex';
        } else if (mode === 'edit') {
            if (btnEdit) btnEdit.className = activeClass;
            if (editorGrid) editorGrid.className = 'flex flex-col flex-1 min-h-[580px]';
            if (editorPaneLeft) editorPaneLeft.style.display = 'flex';
            if (editorPaneRight) editorPaneRight.style.display = 'none';
        } else if (mode === 'preview') {
            if (btnPreview) btnPreview.className = activeClass;
            if (editorGrid) editorGrid.className = 'flex flex-col flex-1 min-h-[580px]';
            if (editorPaneLeft) editorPaneLeft.style.display = 'none';
            if (editorPaneRight) editorPaneRight.style.display = 'flex';
        }
    }

    function toggleFullscreen() {
        wrapper.classList.toggle('editor-fullscreen');
        if (wrapper.classList.contains('editor-fullscreen')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // 工具栏事件委托：按容器挂载并分发动作，兼容现有 onclick 或统一 data-action
    wrapper.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('[data-action]');
        if (!actionBtn) return;
        const action = actionBtn.dataset.action;
        if (action === 'insert') {
            insertSyntax(actionBtn.dataset.prefix || '', actionBtn.dataset.suffix || '');
        } else if (action === 'table') {
            insertTable();
        } else if (action === 'mode') {
            switchMode(actionBtn.dataset.mode);
        } else if (action === 'fullscreen') {
            toggleFullscreen();
        }
    });

    // 仍兼容 window 辅助方法直接调用
    window.insertSyntax = insertSyntax;
    window.insertTable = insertTable;
    window.switchMode = switchMode;
    window.toggleFullscreen = toggleFullscreen;

    // 拖拽与剪贴板图片上传
    if (dropOverlay) {
        textarea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropOverlay.classList.remove('hidden');
        });
        textarea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropOverlay.classList.add('hidden');
        });
        textarea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropOverlay.classList.add('hidden');
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                insertSyntax(`![${files[0].name}](`, `https://placehold.co/800x400/f5f5f4/57534e?text=${encodeURIComponent(files[0].name)})`);
            }
        });
    }

    textarea.addEventListener('paste', (e) => {
        const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
        for (let item of items) {
            if (item.kind === 'file' && item.type.startsWith('image/')) {
                const filename = 'screenshot-' + Date.now() + '.webp';
                insertSyntax(`![${filename}](`, `https://placehold.co/800x400/f5f5f4/57534e?text=${encodeURIComponent(filename)})`);
                break;
            }
        }
    });

    renderLivePreview();
}

// 自动扫描与挂载
export function autoInitPostEditors() {
    document.querySelectorAll('[data-post-editor]').forEach((el) => {
        initPostEditor(el);
    });
}
