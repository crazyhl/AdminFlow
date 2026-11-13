<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '控制台') · AdminFlow</title>

    <!-- Vite 构建产物 (Tailwind CSS v4 + Preline UI 本地按需编译) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- 图标库 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- 防闪烁深色模式与 Mini 侧边栏加载脚本 (Anti-FOUC & 默认跟随系统) -->
    <script>
        (function() {
            // 深浅色主题偏好
            const stored = localStorage.getItem('theme');
            const theme = (stored === 'light' || stored === 'dark') ? stored : 'system';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
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
</head>
<body class="h-full flex bg-[#fbfbfa] dark:bg-[#191919] text-stone-800 dark:text-stone-200 antialiased selection:bg-stone-200 dark:selection:bg-stone-700 transition-colors duration-150">

    <!-- Preline UI 侧边栏：Nordic Minimal 暖调极简 (支持移动端抽屉与桌面端 Mini 模式) -->
    <aside id="hs-application-sidebar" class="hs-overlay [--auto-close:lg] hs-overlay-open:translate-x-0 -translate-x-full transition-all duration-200 transform fixed inset-y-0 start-0 z-[60] w-64 bg-[#f7f6f3] dark:bg-[#202020] border-e border-stone-200/70 dark:border-stone-800 flex flex-col justify-between shrink-0 lg:static lg:block lg:translate-x-0" tabindex="-1" aria-label="侧边栏">
        <div class="flex-1 flex flex-col min-h-0">
            <!-- 工作空间标识 -->
            <div class="h-14 flex items-center justify-between px-4 border-b border-stone-200/60 dark:border-stone-800 sidebar-header-box">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 flex items-center justify-center font-serif-nordic text-xs font-bold shrink-0 shadow-xs">
                        AF
                    </div>
                    <span class="text-xs font-semibold tracking-tight text-stone-900 dark:text-stone-100 sidebar-brand-name truncate">AdminFlow</span>
                </a>
                <div class="flex items-center gap-1.5 sidebar-label">
                    <span class="text-[10px] bg-stone-200/70 dark:bg-stone-800 text-stone-600 dark:text-stone-400 px-1.5 py-0.5 rounded font-mono">v1.0</span>
                    <!-- 移动端关闭按钮 -->
                    <button type="button" class="lg:hidden p-1 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200" data-hs-overlay="#hs-application-sidebar" aria-label="关闭侧边栏">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- 导航菜单 -->
            <nav class="p-3 space-y-5 text-xs overflow-y-auto flex-1">
                <div>
                    <div class="text-[11px] font-medium text-stone-400 dark:text-stone-500 px-2 mb-1.5 sidebar-section-title">控制台与底座</div>
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-nav-link flex items-center gap-2.5 px-2.5 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-stone-200/70 dark:bg-stone-800 text-stone-900 dark:text-stone-100' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60' }} transition-colors" title="控制台仪表盘">
                        <i class="bi bi-compass text-sm shrink-0"></i>
                        <span class="sidebar-label truncate">控制台仪表盘</span>
                    </a>
                    <a href="#" class="sidebar-nav-link flex items-center gap-2.5 px-2.5 py-1.5 rounded-md font-medium text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60 mt-0.5 transition-colors" title="操作审计日志">
                        <i class="bi bi-clock-history text-sm shrink-0"></i>
                        <span class="sidebar-label truncate">操作审计日志</span>
                    </a>
                </div>

                <div>
                    <div class="text-[11px] font-medium text-stone-400 dark:text-stone-500 px-2 mb-1.5 sidebar-section-title">成员与权限</div>
                    <a href="#" class="sidebar-nav-link flex items-center justify-between px-2.5 py-1.5 rounded-md font-medium text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60 transition-colors" title="管理员列表">
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <i class="bi bi-people text-sm shrink-0"></i>
                            <span class="sidebar-label truncate">管理员列表</span>
                        </div>
                        <span class="sidebar-badge text-[10px] bg-stone-200/60 dark:bg-stone-800 text-stone-600 dark:text-stone-400 px-1.5 py-0.5 rounded font-mono shrink-0">3</span>
                    </a>
                </div>

                <div>
                    <div class="text-[11px] font-medium text-stone-400 dark:text-stone-500 px-2 mb-1.5 sidebar-section-title">创作工坊 (CMS 插件)</div>
                    <a href="#" class="sidebar-nav-link flex items-center gap-2.5 px-2.5 py-1.5 rounded-md font-medium text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60 transition-colors" title="Markdown 文章写作">
                        <i class="bi bi-pen text-sm shrink-0"></i>
                        <span class="sidebar-label truncate">Markdown 文章写作</span>
                    </a>
                    <a href="#" class="sidebar-nav-link flex items-center justify-between px-2.5 py-1.5 rounded-md font-medium text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60 mt-0.5 transition-colors" title="访客评论互动">
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <i class="bi bi-chat-quote text-sm shrink-0"></i>
                            <span class="sidebar-label truncate">访客评论互动</span>
                        </div>
                        <span class="sidebar-badge w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                    </a>
                    <a href="#" class="sidebar-nav-link flex items-center gap-2.5 px-2.5 py-1.5 rounded-md font-medium text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60 mt-0.5 transition-colors" title="图床媒体资源">
                        <i class="bi bi-images text-sm shrink-0"></i>
                        <span class="sidebar-label truncate">图床媒体资源</span>
                    </a>
                </div>
            </nav>
        </div>
    </aside>

    <!-- 主工作区 -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- 顶栏：面包屑 + Mini 切换 + 暗色模式切换 + 用户下拉菜单 -->
        <header class="h-14 bg-white/80 dark:bg-[#191919]/90 backdrop-blur px-6 lg:px-8 flex items-center justify-between border-b border-stone-200/60 dark:border-stone-800 sticky top-0 z-10 transition-colors duration-150">
            <div class="flex items-center gap-3">
                <!-- 移动端侧边抽屉开关 (Preline Overlay) -->
                <button type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors" data-hs-overlay="#hs-application-sidebar" aria-controls="hs-application-sidebar" aria-label="打开侧边栏">
                    <i class="bi bi-list text-lg"></i>
                </button>

                <!-- 桌面端 Mini 侧边栏折叠/展开开关 -->
                <button type="button" onclick="toggleSidebarMini()" class="hidden lg:flex w-8 h-8 items-center justify-center rounded-lg text-stone-400 hover:text-stone-800 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors" title="切换 Mini 紧凑侧边栏">
                    <i id="sidebar-mini-icon" class="bi bi-layout-sidebar-inset-reverse text-sm"></i>
                </button>

                <!-- 面包屑 -->
                <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                    <span class="text-stone-400 dark:text-stone-500">AdminFlow</span>
                    <span class="text-stone-300 dark:text-stone-700">/</span>
                    <span class="font-medium text-stone-800 dark:text-stone-200">@yield('breadcrumb', '控制台')</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- 暗黑模式三态切换 (浅色 / 跟随系统 / 深色) -->
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

                <div class="h-4 w-px bg-stone-200 dark:bg-stone-800"></div>

                <!-- 用户信息与登出弹出菜单 (Preline UI hs-dropdown) -->
                <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
                    <button id="hs-dropdown-avatar" type="button" class="hs-dropdown-toggle inline-flex items-center gap-2 p-1 pl-1.5 pr-2 rounded-full hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-700 dark:text-stone-200 transition-colors focus:outline-hidden" aria-haspopup="menu" aria-expanded="false" aria-label="用户菜单">
                        <div class="w-7 h-7 rounded-full bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                            {{ strtoupper(substr(auth('admin')->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <span class="text-xs font-medium max-w-[120px] truncate hidden sm:inline">{{ auth('admin')->user()->name ?? '管理员' }}</span>
                        <i class="bi bi-chevron-down text-[10px] text-stone-400"></i>
                    </button>

                    <div class="hs-dropdown-menu hs-dropdown-open:opacity-100 w-56 transition-[opacity,margin] duration opacity-0 hidden z-50 bg-white dark:bg-[#202020] rounded-xl shadow-lg border border-stone-200/80 dark:border-stone-800 divide-y divide-stone-100 dark:divide-stone-800" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-avatar">
                        <!-- 用户基础信息卡片 -->
                        <div class="py-2.5 px-3.5 space-y-0.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-stone-900 dark:text-stone-100 truncate">{{ auth('admin')->user()->name ?? '管理员' }}</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                                    {{ auth('admin')->user()->role?->value ?? 'admin' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-stone-400 truncate">{{ auth('admin')->user()->email ?? '' }}</p>
                        </div>

                        <!-- 业务菜单项 -->
                        <div class="p-1 text-xs">
                            <a href="#" class="flex items-center gap-2.5 py-1.5 px-2.5 rounded-lg text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800/80 hover:text-stone-900 dark:text-stone-200 transition-colors">
                                <i class="bi bi-person text-sm"></i>
                                <span>个人资料</span>
                            </a>
                            <a href="#" class="flex items-center gap-2.5 py-1.5 px-2.5 rounded-lg text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800/80 hover:text-stone-900 dark:text-stone-200 transition-colors">
                                <i class="bi bi-shield-check text-sm"></i>
                                <span>安全设置</span>
                            </a>
                        </div>

                        <!-- 退出登录动作 -->
                        <div class="p-1 text-xs">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 py-1.5 px-2.5 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors text-left">
                                    <i class="bi bi-box-arrow-right text-sm"></i>
                                    <span>安全退出登录</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 页面主体容器 -->
        <main class="p-8 max-w-5xl space-y-8">
            @yield('content')
        </main>
    </div>

</body>
</html>
