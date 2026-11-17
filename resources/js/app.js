import 'preline';
import htmx from 'htmx.org';

// 挂载全局 htmx 实例
window.htmx = htmx;

// 1. 自动注入 Laravel CSRF Token
document.addEventListener('htmx:configRequest', (event) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
    }
});

// 2. htmx 局部置换 DOM 后，自动重新挂载 Preline UI 组件 (下拉菜单、折叠卡片、模态窗等)
document.addEventListener('htmx:afterSwap', () => {
    window.HSStaticMethods?.autoInit();
});

// 3. 支持服务端通过 HX-Trigger 响应头触发全局 Toast 消息
document.addEventListener('showToast', (event) => {
    const detail = event.detail || {};
    if (window.showToast && detail.message) {
        window.showToast(detail.message, detail.type || 'info');
    }
});
