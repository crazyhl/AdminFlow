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
                <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-nav-link flex items-center gap-2.5 px-2.5 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.audit-logs.*') ? 'bg-stone-200/70 dark:bg-stone-800 text-stone-900 dark:text-stone-100' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60' }} mt-0.5 transition-colors" title="操作审计日志">
                    <i class="bi bi-clock-history text-sm shrink-0"></i>
                    <span class="sidebar-label truncate">操作审计日志</span>
                </a>
            </div>

            <div>
                <div class="text-[11px] font-medium text-stone-400 dark:text-stone-500 px-2 mb-1.5 sidebar-section-title">成员与权限</div>
                <a href="{{ route('admin.users.index') }}" class="sidebar-nav-link flex items-center justify-between px-2.5 py-1.5 rounded-md font-medium {{ request()->routeIs('admin.users.*') ? 'bg-stone-200/70 dark:bg-stone-800 text-stone-900 dark:text-stone-100' : 'text-stone-600 dark:text-stone-400 hover:bg-stone-200/40 dark:hover:bg-stone-800/60' }} transition-colors" title="成员列表与授权">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <i class="bi bi-people text-sm shrink-0"></i>
                        <span class="sidebar-label truncate">成员列表与授权</span>
                    </div>
                    <span class="sidebar-badge text-[10px] bg-stone-200/60 dark:bg-stone-800 text-stone-600 dark:text-stone-400 px-1.5 py-0.5 rounded font-mono shrink-0">
                        {{ \AdminFlow\Core\Models\User::count() }}
                    </span>
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
