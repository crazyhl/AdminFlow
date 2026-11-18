<!-- 防闪烁深色模式与 Mini 侧边栏加载脚本 (Anti-FOUC & 默认跟随系统) -->
<script>
    (function() {
        // 深浅色主题偏好
        const stored = localStorage.getItem('theme');
        const theme = (stored === 'light' || stored === 'dark') ? stored : 'system';
        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) {
            document.documentElement.classList.add('dark');
            document.documentElement.style.backgroundColor = '#191919';
            document.documentElement.style.colorScheme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.backgroundColor = '#fbfbfa';
            document.documentElement.style.colorScheme = 'light';
        }

        // 侧边栏 Mini 模式记忆 (桌面端折叠)
        if (localStorage.getItem('sidebar_mode') === 'mini') {
            document.documentElement.classList.add('sidebar-mini');
        }
    })();

    function getStoredTheme() {
        const stored = localStorage.getItem('theme');
        return (stored === 'light' || stored === 'dark') ? stored : 'system';
    }

    function applyTheme(theme) {
        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) {
            document.documentElement.classList.add('dark');
            document.documentElement.style.backgroundColor = '#191919';
            document.documentElement.style.colorScheme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.backgroundColor = '#fbfbfa';
            document.documentElement.style.colorScheme = 'light';
        }
        updateThemeUI(theme);
    }

    function setTheme(theme) {
        if (theme === 'system') {
            localStorage.removeItem('theme');
        } else {
            localStorage.setItem('theme', theme);
        }
        applyTheme(theme);
    }

    function updateThemeUI(theme) {
        ['light', 'system', 'dark'].forEach(t => {
            const btn = document.getElementById('theme-btn-' + t);
            if (btn) {
                if (t === theme) {
                    btn.className = 'p-1 px-1.5 rounded-md bg-white dark:bg-stone-700 text-stone-900 dark:text-stone-100 shadow-sm transition-colors';
                } else {
                    btn.className = 'p-1 px-1.5 rounded-md text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 transition-colors';
                }
            }
        });
    }

    // 侧边栏 Mini 模式切换
    function toggleSidebarMini() {
        const isMini = document.documentElement.classList.toggle('sidebar-mini');
        localStorage.setItem('sidebar_mode', isMini ? 'mini' : 'expanded');
        updateSidebarMiniUI();
    }

    function updateSidebarMiniUI() {
        const isMini = document.documentElement.classList.contains('sidebar-mini');
        const icon = document.getElementById('sidebar-mini-icon');
        if (icon) {
            if (isMini) {
                icon.className = 'bi bi-layout-sidebar-inset text-stone-900 dark:text-stone-100 text-sm';
            } else {
                icon.className = 'bi bi-layout-sidebar-inset-reverse text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 text-sm';
            }
        }
    }

    // 监听系统深浅色偏好动态变化
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getStoredTheme() === 'system') {
            applyTheme('system');
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        updateThemeUI(getStoredTheme());
        updateSidebarMiniUI();
    });
</script>
