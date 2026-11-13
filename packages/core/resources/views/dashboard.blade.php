@extends('adminflow-core::layouts.admin')

@section('title', '仪表盘')
@section('breadcrumb', '控制台仪表盘')

@section('content')
    <!-- 问候与核心心智 -->
    <div class="space-y-1">
        <h1 class="text-2xl font-serif-nordic font-semibold text-stone-900 dark:text-stone-100 tracking-tight">
            午后好，系统运行平稳。
        </h1>
        <p class="text-xs text-stone-500 dark:text-stone-400">
            AdminFlow 单体微内核持续护航中 · 具备完整版本并发保护（409 防覆盖）与防越权服务端控制。
        </p>
    </div>

    <!-- 扁平数字指标 (Nordic Minimal 纯粹排印) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-5 border-y border-stone-200/60 dark:border-stone-800">
        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">已沉淀文章</div>
            <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">42</div>
            <div class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1 flex items-center gap-1 font-mono">
                <i class="bi bi-check2"></i> 38 篇已发布
            </div>
        </div>

        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">待审核评论</div>
            <div class="text-3xl font-serif-nordic font-bold text-amber-700 dark:text-amber-400 mt-1">7</div>
            <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">
                待审核确认防 XSS 注入
            </div>
        </div>

        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">媒体库占用</div>
            <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                1.2 <span class="text-sm font-sans font-normal text-stone-400">GB</span>
            </div>
            <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">
                186 张高清图床
            </div>
        </div>

        <div>
            <div class="text-xs text-stone-400 dark:text-stone-500">累计操作审计</div>
            <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                {{ \AdminFlow\Core\Models\AuditLog::query()->count() }}
            </div>
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                敏感口令强制脱敏
            </div>
        </div>
    </div>

    <!-- 下方两栏：最新操作审计日志 vs 底座系统规格 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- 左侧 2 列：最新操作审计日志流水 -->
        <div class="md:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 tracking-wide uppercase">
                    最新操作审计日志
                </div>
                <span class="text-[11px] text-stone-400 font-mono">最近 5 条</span>
            </div>

            <div class="bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 divide-y divide-stone-100 dark:divide-stone-800/80 shadow-[0_1px_3px_rgba(0,0,0,0.02)] transition-colors duration-150">
                @forelse($auditLogs as $log)
                    <div class="p-4 flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-medium {{ str_starts_with($log->action, 'auth.') ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60' : 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300' }}">
                                    {{ $log->action }}
                                </span>
                                <span class="text-xs font-medium text-stone-800 dark:text-stone-200">
                                    {{ $log->user?->name ?? '系统访客/CLI' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-stone-500 dark:text-stone-400 font-mono">
                                IP: {{ $log->ip_address ?? '-' }} · 客户端: {{ Str::limit($log->user_agent, 40) }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-[11px] text-stone-400 dark:text-stone-500 font-mono">
                                {{ $log->created_at?->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-stone-400">暂无审计记录</div>
                @endforelse
            </div>
        </div>

        <!-- 右侧 1 列：底座技术架构状态 -->
        <div class="space-y-4">
            <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 tracking-wide uppercase">
                底座运行环境
            </div>
            <div class="bg-[#f7f6f3] dark:bg-[#202020] p-5 rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-3.5 text-xs transition-colors duration-150">
                <div class="flex items-center justify-between">
                    <span class="text-stone-500 dark:text-stone-400">PHP 运行环境</span>
                    <span class="font-mono font-medium text-stone-800 dark:text-stone-200">8.4.27 (Sail)</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-stone-500 dark:text-stone-400">Laravel 框架</span>
                    <span class="font-mono font-medium text-stone-800 dark:text-stone-200">12.x 最新稳定</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-stone-500 dark:text-stone-400">MySQL 数据库</span>
                    <span class="font-mono font-medium text-stone-800 dark:text-stone-200">8.4 LTS</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-stone-500 dark:text-stone-400">会话与限流驱动</span>
                    <span class="text-emerald-700 dark:text-emerald-400 font-medium">Database (Sessions & Cache)</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-stone-500 dark:text-stone-400">代码质检工具链</span>
                    <span class="text-stone-800 dark:text-stone-200 font-mono">Mago 1.59.0 (Rust)</span>
                </div>
            </div>
        </div>
    </div>
@endsection
