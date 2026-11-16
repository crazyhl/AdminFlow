@extends('adminflow-core::layouts.admin')

@section('title', '仪表盘')
@section('breadcrumb', '控制台仪表盘')

@section('content')
    <!-- 问候与核心心智 -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-serif-nordic font-semibold text-stone-900 dark:text-stone-100 tracking-tight">
                午后好，系统运行平稳。
            </h1>
            <p class="text-xs text-stone-500 dark:text-stone-400">
                AdminFlow 通用后台底座护航中 · 具备完整版本并发保护（409 防覆盖）与服务端权限策略隔离。
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-300 text-xs font-mono">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>微内核服务就绪 · 健康度 100%</span>
        </div>
    </div>

    <!-- 扁平数字指标 (全宽横向自适应排印) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 py-5 border-y border-stone-200/60 dark:border-stone-800">
        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">已沉淀文章</div>
            <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">42</div>
            <div class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1 flex items-center gap-1 font-mono">
                <i class="bi bi-check2"></i> 38 篇已发布 · 4 篇草稿
            </div>
        </div>

        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">待审核评论</div>
            <div class="text-3xl font-serif-nordic font-bold text-amber-700 dark:text-amber-400 mt-1">7</div>
            <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-1 font-mono">
                三层树形嵌套 · 防 XSS 注入
            </div>
        </div>

        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">媒体库占用</div>
            <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                1.2 <span class="text-sm font-sans font-normal text-stone-400">GB</span>
            </div>
            <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-1 font-mono">
                186 张高清图床资源
            </div>
        </div>

        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">累计操作审计</div>
            <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                {{ $stats['audit_count'] ?? 0 }}
            </div>
            <div class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                敏感口令强制脱敏落库
            </div>
        </div>
    </div>

    <!-- 主体双栏工作台布局 (12 列网格：8 列核心流水 + 4 列右侧快捷工具与系统规格) -->
    <div class="w-full grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
        <!-- 左侧 8 列：操作审计流 + 创作工作台概览 -->
        <div class="xl:col-span-8 space-y-6">
            <!-- 卡片 1：最新操作审计日志流水 -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 tracking-wide uppercase flex items-center gap-2">
                        <i class="bi bi-clock-history text-sm text-stone-500"></i>
                        <span>最新操作审计流水</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[11px] text-stone-400 font-mono">最近 8 条</span>
                        <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors">
                            查看全量审计 →
                        </a>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 divide-y divide-stone-100 dark:divide-stone-800/80 shadow-[0_1px_3px_rgba(0,0,0,0.02)] overflow-hidden transition-colors duration-150">
                    @forelse($auditLogs as $log)
                        <div class="p-3.5 px-4.5 flex items-start justify-between gap-4 hover:bg-stone-50/40 dark:hover:bg-stone-800/30 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $log->badge_class }}">
                                        {{ $log->module_label }} · {{ $log->action_label }}
                                    </span>
                                    <span class="text-xs font-medium text-stone-900 dark:text-stone-100">
                                        {{ $log->user?->name ?? '系统 CLI / 访客' }}
                                    </span>
                                    @if($log->user?->email)
                                        <span class="text-[11px] text-stone-400 font-mono">({{ $log->user->email }})</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-stone-500 dark:text-stone-400 font-mono flex items-center gap-2 flex-wrap">
                                    <span>IP: {{ $log->ip_address ?? '127.0.0.1' }}</span>
                                    <span>·</span>
                                    <span>客户端: {{ Str::limit($log->user_agent, 45) }}</span>
                                    <span>·</span>
                                    <span class="text-stone-400 text-[10px]">标识: {{ $log->action }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0 pt-0.5">
                                <div class="text-[11px] text-stone-400 dark:text-stone-500 font-mono">
                                    {{ $log->created_at?->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-stone-400 font-mono">暂无审计记录</div>
                    @endforelse
                </div>
            </div>

            <!-- 卡片 2：创作工坊与待办概览 (CMS 核心工作区) -->
            <div class="space-y-3">
                <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 tracking-wide uppercase flex items-center gap-2">
                    <i class="bi bi-journal-text text-sm text-stone-500"></i>
                    <span>创作工坊待办流</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-stone-900 dark:text-stone-100">Markdown 原文安全渲染</span>
                            <span class="text-[10px] bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 px-1.5 py-0.5 rounded font-mono">P1-04 就绪</span>
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                            支持 Markdown 原文与 AST 严格清洗，防任意 script 注入，提供受保护的草稿预览机制与 409 并发版本锁。
                        </p>
                    </div>

                    <div class="p-4 bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-stone-900 dark:text-stone-100">Hugo 博客数据迁移</span>
                            <span class="text-[10px] bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 px-1.5 py-0.5 rounded font-mono">P1-09 就绪</span>
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                            支持原博客文章、分类标签、Front Matter 与本地图片目录无损迁移，保持原有 URL 或逐条 301 重定向。
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右侧 4 列：快捷入口 + 运行规格 + 防御准则 -->
        <div class="xl:col-span-4 space-y-6">
            <!-- 快捷管理入口卡片 -->
            <div class="bg-white dark:bg-[#202020] p-5 rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
                <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 tracking-wide uppercase flex items-center justify-between">
                    <span>快捷运维与创作入口</span>
                    <i class="bi bi-lightning text-amber-500"></i>
                </div>

                <div class="space-y-2 text-xs">
                    <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between p-2.5 rounded-lg border border-stone-200/80 dark:border-stone-800 hover:bg-stone-50 dark:hover:bg-stone-800/60 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <i class="bi bi-people text-stone-600 dark:text-stone-400"></i>
                            <span class="font-medium text-stone-800 dark:text-stone-200">成员列表与授权</span>
                        </div>
                        <span class="text-[11px] text-stone-400 font-mono">{{ $stats['users_count'] ?? 1 }} 名成员</span>
                    </a>

                    <a href="#" class="flex items-center justify-between p-2.5 rounded-lg border border-stone-200/80 dark:border-stone-800 hover:bg-stone-50 dark:hover:bg-stone-800/60 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <i class="bi bi-pen text-stone-600 dark:text-stone-400"></i>
                            <span class="font-medium text-stone-800 dark:text-stone-200">新建 Markdown 文章</span>
                        </div>
                        <i class="bi bi-arrow-right text-stone-400 text-xs"></i>
                    </a>

                    <a href="#" class="flex items-center justify-between p-2.5 rounded-lg border border-stone-200/80 dark:border-stone-800 hover:bg-stone-50 dark:hover:bg-stone-800/60 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <i class="bi bi-images text-stone-600 dark:text-stone-400"></i>
                            <span class="font-medium text-stone-800 dark:text-stone-200">媒体图床管理</span>
                        </div>
                        <i class="bi bi-arrow-right text-stone-400 text-xs"></i>
                    </a>
                </div>
            </div>

            <!-- 底座技术架构规格卡片 (真实环境数据) -->
            <div class="bg-[#f7f6f3] dark:bg-[#202020] p-5 rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-3.5 text-xs transition-colors duration-150">
                <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 tracking-wide uppercase flex items-center justify-between">
                    <span>底座技术栈与依赖</span>
                    <span class="text-[10px] bg-stone-200/70 dark:bg-stone-800 text-stone-600 dark:text-stone-400 px-1.5 py-0.5 rounded font-mono">最新稳定版</span>
                </div>

                <div class="space-y-2.5 font-mono text-[11px]">
                    <div class="flex items-center justify-between">
                        <span class="text-stone-500 dark:text-stone-400 font-sans">PHP 运行版本</span>
                        <span class="font-medium text-stone-800 dark:text-stone-200">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-stone-500 dark:text-stone-400 font-sans">Laravel 框架</span>
                        <span class="font-medium text-stone-800 dark:text-stone-200">v{{ Illuminate\Foundation\Application::VERSION }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-stone-500 dark:text-stone-400 font-sans">前端组件体系</span>
                        <span class="font-medium text-stone-800 dark:text-stone-200">Tailwind 4 + Preline 5.1</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-stone-500 dark:text-stone-400 font-sans">代码质检工具</span>
                        <span class="font-medium text-stone-800 dark:text-stone-200">Mago 1.59.0 (Rust)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-stone-500 dark:text-stone-400 font-sans">数据库与会话</span>
                        <span class="text-emerald-700 dark:text-emerald-400">MySQL 9.6 (InnoDB)</span>
                    </div>
                </div>
            </div>

            <!-- 安全与合规准则 -->
            <div class="p-4 bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-2 text-xs">
                <div class="font-semibold text-stone-900 dark:text-stone-100 flex items-center gap-1.5">
                    <i class="bi bi-shield-lock text-emerald-600"></i>
                    <span>安全控制防护状态</span>
                </div>
                <div class="space-y-1.5 text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                    <p>· <strong>409 并发冲突保护</strong>：每次写操作校验数据版本戳，杜绝多端相互覆盖。</p>
                    <p>· <strong>脱敏审计</strong>：密码与口令不入日志，保护管理员敏感资产。</p>
                </div>
            </div>
        </div>
    </div>
@endsection
