import 'preline';
import htmx from 'htmx.org';
import flatpickr from 'flatpickr';
import { Mandarin } from 'flatpickr/dist/l10n/zh.js';

// 挂载全局 flatpickr 实例并应用中文本地化
flatpickr.localize(Mandarin);
window.flatpickr = flatpickr;

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

// 3. 允许 HTTP 422 校验失败与 HTTP 409 并发冲突响应正常执行 DOM Swap (保持表单打开且呈现局部错误或冲突卡片)
document.addEventListener('htmx:beforeSwap', (event) => {
    const status = event.detail.xhr.status;
    if (status === 422 || status === 409) {
        event.detail.shouldSwap = true;
        event.detail.isError = false;
    }
});

// 4. 会话过期与未认证拦截 (401 / 419) 平滑跳转登录页
document.addEventListener('htmx:responseError', (event) => {
    const status = event.detail.xhr.status;
    if (status === 401 || status === 419) {
        window.location.href = '/admin/login';
    }
});

// 5. 支持服务端通过 HX-Trigger 响应头触发全局 Toast 消息 (含 HTTP Header Mojibake 兼容还原)
document.addEventListener('showToast', (event) => {
    const detail = event.detail || {};
    let message = detail.message;
    if (window.showToast && message) {
        try {
            if (/[\u0080-\u00ff]/.test(message)) {
                const bytes = Uint8Array.from([...message].map((c) => c.charCodeAt(0)));
                const decoded = new TextDecoder('utf-8').decode(bytes);
                if (decoded && !decoded.includes('\ufffd')) {
                    message = decoded;
                }
            }
        } catch (_) {}
        window.showToast(message, detail.type || 'info');
    }
});

// 6. 支持文章保存成功后自动更新隐藏域版本号与界面徽章 (无刷新零闪白)
document.addEventListener('postSaved', (event) => {
    const detail = event.detail || {};
    if (detail.version !== undefined) {
        // 同步更新页面所有 lock_version 隐藏域（包括主编辑表单及删除二次确认弹窗）
        document.querySelectorAll('input[name="lock_version"]').forEach((input) => {
            input.value = detail.version;
        });

        // 同步更新删除模态窗 form 的 action URL 中的 lock_version 查询参数
        document.querySelectorAll('form[action*="lock_version="]').forEach((form) => {
            try {
                const url = new URL(form.action, window.location.origin);
                url.searchParams.set('lock_version', detail.version);
                form.action = url.pathname + url.search;
            } catch (_) {}
        });

        const badge = document.getElementById('post-version-badge');
        if (badge) {
            badge.innerText = `v${detail.version}`;
        }
        const alertContainer = document.getElementById('form-alert-container');
        if (alertContainer) {
            alertContainer.innerHTML = '';
        }
    }
});

// 6. 全局自动初始化 Flatpickr 日期时间选择器 (支持首次渲染与 HTMX 局部置换)
const initFlatpickrInputs = () => {
    document.querySelectorAll('[data-flatpickr]').forEach((el) => {
        if (!el._flatpickr) {
            flatpickr(el, {
                enableTime: true,
                dateFormat: 'Y-m-d H:i',
                time_24hr: true,
                allowInput: true,
                defaultDate: el.value || new Date(),
                locale: Mandarin
            });
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFlatpickrInputs);
} else {
    initFlatpickrInputs();
}
document.addEventListener('htmx:afterSwap', initFlatpickrInputs);
window.addEventListener('load', initFlatpickrInputs);
