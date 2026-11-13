<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminFlow 登录</title>

    <!-- Vite 构建产物 (Tailwind CSS v4 + Preline UI 本地按需编译) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- 防闪烁深色模式加载脚本 (Anti-FOUC & 默认跟随系统: system / light / dark) -->
    <script>
        (function() {
            const stored = localStorage.getItem('theme');
            const theme = (stored === 'light' || stored === 'dark') ? stored : 'system';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
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
            } else {
                document.documentElement.classList.remove('dark');
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

        // 监听系统深浅色偏好动态变化
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (getStoredTheme() === 'system') {
                applyTheme('system');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            updateThemeUI(getStoredTheme());
        });
    </script>
</head>
<body class="h-full flex flex-col items-center justify-center p-6 bg-[#fbfbfa] dark:bg-[#191919] text-stone-800 dark:text-stone-200 antialiased selection:bg-stone-200 dark:selection:bg-stone-700 transition-colors duration-150">

    <!-- 顶部右上角：主题模式三态切换 (浅色 / 跟随系统 / 深色) -->
    <div class="fixed top-6 right-6">
        <div class="inline-flex items-center p-0.5 rounded-lg bg-stone-100 dark:bg-stone-800 border border-stone-200/80 dark:border-stone-700/60 text-xs">
            <button type="button" onclick="setTheme('light')" id="theme-btn-light" class="p-1 px-1.5 rounded-md text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 transition-colors" title="浅色模式">
                <i class="bi bi-sun text-xs"></i>
            </button>
            <button type="button" onclick="setTheme('system')" id="theme-btn-system" class="p-1 px-1.5 rounded-md bg-white dark:bg-stone-700 text-stone-900 dark:text-stone-100 shadow-sm transition-colors" title="跟随系统">
                <i class="bi bi-laptop text-xs"></i>
            </button>
            <button type="button" onclick="setTheme('dark')" id="theme-btn-dark" class="p-1 px-1.5 rounded-md text-stone-400 hover:text-stone-700 dark:hover:text-stone-300 transition-colors" title="深色模式">
                <i class="bi bi-moon-stars text-xs"></i>
            </button>
        </div>
    </div>

    <!-- 登录卡片 (Nordic Minimal 暖石卡片 / 暗夜黑曜岩) -->
    <div class="w-full max-w-sm space-y-6">
        <!-- 品牌徽标与标题 -->
        <div class="text-center space-y-2">
            <div class="inline-flex w-10 h-10 rounded-xl bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 items-center justify-center font-serif-nordic text-base font-bold shadow-sm">
                AF
            </div>
            <h1 class="text-2xl font-serif-nordic font-semibold tracking-tight text-stone-900 dark:text-stone-100">
                AdminFlow
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400">
                轻量单体后台基座 · 身份验证网关
            </p>
        </div>

        <!-- 登录表单 -->
        <div class="bg-white dark:bg-[#202020] p-6 rounded-2xl border border-stone-200/80 dark:border-stone-800 shadow-[0_2px_8px_rgba(0,0,0,0.03)] dark:shadow-[0_2px_12px_rgba(0,0,0,0.3)] transition-colors duration-150">
            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4 text-xs">
                @csrf

                <!-- 邮箱字段 -->
                <div class="space-y-1.5">
                    <label for="email" class="block font-medium text-stone-700 dark:text-stone-300">
                        管理员邮箱
                    </label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="admin@example.com"
                        required 
                        autofocus
                        class="w-full px-3 py-2 bg-stone-50 dark:bg-[#191919] border border-stone-200 dark:border-stone-800 rounded-lg text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-stone-400 dark:focus:ring-stone-600 transition-colors font-mono text-xs"
                    >
                    @error('email')
                        <div class="text-[11px] text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- 密码字段 -->
                <div class="space-y-1.5">
                    <label for="password" class="block font-medium text-stone-700 dark:text-stone-300">
                        安全密码
                    </label>
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        placeholder="••••••••"
                        required 
                        class="w-full px-3 py-2 bg-stone-50 dark:bg-[#191919] border border-stone-200 dark:border-stone-800 rounded-lg text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-stone-400 dark:focus:ring-stone-600 transition-colors text-xs"
                    >
                    @error('password')
                        <div class="text-[11px] text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- 记住我 -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-stone-600 dark:text-stone-400 text-xs">
                        <input type="checkbox" name="remember" class="rounded border-stone-300 dark:border-stone-700 text-stone-900 focus:ring-0">
                        <span>记住本次登录</span>
                    </label>
                </div>

                <!-- 提交按钮 -->
                <button 
                    type="submit" 
                    class="w-full mt-2 py-2 px-4 bg-stone-900 hover:bg-stone-800 dark:bg-stone-100 dark:hover:bg-white text-stone-100 dark:text-stone-900 font-medium rounded-lg shadow-sm transition-all text-xs flex items-center justify-center gap-1.5"
                >
                    <span>安全登录</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- 底部环境提示 -->
        <div class="text-center text-[11px] text-stone-400 dark:text-stone-500 font-mono">
            PHP 8.5 · Laravel 13 · Nordic Minimal
        </div>
    </div>

</body>
</html>
