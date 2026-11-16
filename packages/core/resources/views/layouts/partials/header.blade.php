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
