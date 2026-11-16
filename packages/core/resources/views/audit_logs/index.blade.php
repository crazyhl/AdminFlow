@extends('adminflow-core::layouts.admin')

@section('title', '操作审计日志')
@section('breadcrumb', '操作审计日志')

@section('content')
    <div class="w-full space-y-6">
        <!-- 页面标题与操作 -->
        <x-admin::page-header title="操作审计日志" description="全量记录系统关键管理与业务操作 · 口令与敏感参数强制脱敏落库 · 供安全合规追溯审查。">
            <x-slot:badge>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-900/60">
                    不可变存证
                </span>
            </x-slot:badge>
            <x-slot:actions>
                <div class="text-xs text-stone-400 font-mono flex items-center gap-2">
                    <i class="bi bi-shield-check text-emerald-600"></i>
                    <span>自动脱敏保护中</span>
                </div>
            </x-slot:actions>
        </x-admin::page-header>

        <!-- 扁平微指标卡 -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-admin::stat-card label="累计操作审计" :value="$stats['total']" subtext="不可更改/软删" />
            <x-admin::stat-card label="今日发生操作" :value="$stats['today']" subtext="系统实时捕获记录" color="emerald" />
            <x-admin::stat-card label="认证登录事件" :value="$stats['auth']" subtext="包含成功、登出与限流" color="stone" />
            <x-admin::stat-card label="敏感控制事件" :value="$stats['sensitive']" subtext="密码重置/删除/启停" color="amber" />
        </div>

        <!-- 筛选与搜索表单 -->
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <!-- 模块筛选 (中文化) -->
            <select name="module" class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                <option value="">全部业务模块</option>
                @foreach($modules as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['module'] ?? '') === $key ? 'selected' : '' }}>
                        {{ $label }} ({{ $key }})
                    </option>
                @endforeach
            </select>

            <!-- 操作人筛选 -->
            <select name="user_id" class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                <option value="">全部操作人</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>

            <!-- 时间范围筛选 -->
            <select name="date_range" class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                <option value="">全部时间跨度</option>
                <option value="today" {{ ($filters['date_range'] ?? '') === 'today' ? 'selected' : '' }}>今天</option>
                <option value="week" {{ ($filters['date_range'] ?? '') === 'week' ? 'selected' : '' }}>最近 7 天</option>
                <option value="month" {{ ($filters['date_range'] ?? '') === 'month' ? 'selected' : '' }}>最近 30 天</option>
            </select>

            <!-- 搜索框 (IP / UA) -->
            <div class="relative flex-1 min-w-[200px]">
                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3 text-stone-400">
                    <i class="bi bi-search text-xs"></i>
                </div>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="搜索 IP 地址、客户端设备、动作标识..." class="py-1.5 ps-8 pe-3 block w-full bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 placeholder:text-stone-400 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
            </div>

            <button type="submit" class="px-3 py-1.5 bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-lg text-xs font-medium transition-colors">
                筛选
            </button>

            @if(!empty($filters['module']) || !empty($filters['user_id']) || !empty($filters['date_range']) || !empty($filters['search']))
                <a href="{{ route('admin.audit-logs.index') }}" class="text-xs text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                    重置
                </a>
            @endif
        </form>

        <!-- 审计日志数据表格 -->
        <div class="bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 shadow-[0_1px_3px_rgba(0,0,0,0.02)] overflow-hidden transition-colors duration-150">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300 divide-y divide-stone-100 dark:divide-stone-800">
                    <thead class="bg-stone-50/60 dark:bg-stone-900/40 text-[11px] font-medium text-stone-400 dark:text-stone-500 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3 px-4">触发时间</th>
                            <th scope="col" class="py-3 px-4">业务模块</th>
                            <th scope="col" class="py-3 px-4">操作动作</th>
                            <th scope="col" class="py-3 px-4">操作执行者</th>
                            <th scope="col" class="py-3 px-4">网络与环境</th>
                            <th scope="col" class="py-3 px-4 text-right">审计明细</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800/80">
                        @forelse($logs as $log)
                            <tr class="hover:bg-stone-50/40 dark:hover:bg-stone-800/30 transition-colors">
                                <!-- 触发时间 -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-mono text-stone-800 dark:text-stone-200 text-xs">
                                        {{ $log->created_at?->format('Y-m-d H:i:s') ?? '-' }}
                                    </div>
                                    <div class="text-[10px] text-stone-400 dark:text-stone-500 font-mono mt-0.5">
                                        {{ $log->created_at?->diffForHumans() }}
                                    </div>
                                </td>

                                <!-- 业务模块 (中文化徽标) -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $log->badge_class }}">
                                        {{ $log->module_label }}
                                    </span>
                                </td>

                                <!-- 操作动作 (中文化大字 + 英文标识) -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-medium text-stone-900 dark:text-stone-100 text-xs flex items-center gap-1.5">
                                        <span>{{ $log->action_label }}</span>
                                    </div>
                                    <div class="text-[10px] text-stone-400 dark:text-stone-500 font-mono mt-0.5">
                                        {{ $log->action }}
                                    </div>
                                </td>

                                <!-- 操作执行者 -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 flex items-center justify-center font-bold text-[10px] shrink-0 border border-stone-200/60 dark:border-stone-700">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-stone-800 dark:text-stone-200 text-xs">
                                                    {{ $log->user->name }}
                                                </div>
                                                <div class="text-[10px] text-stone-400 font-mono">
                                                    {{ $log->user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-stone-500 dark:text-stone-400 text-xs font-mono">
                                            系统 CLI / 匿名访客
                                        </span>
                                    @endif
                                </td>

                                <!-- 网络与环境 -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-mono text-[11px] text-stone-600 dark:text-stone-400">
                                        IP: {{ $log->ip_address ?? '127.0.0.1' }}
                                    </div>
                                    <div class="text-[10px] text-stone-400 dark:text-stone-500 font-mono truncate max-w-[200px]" title="{{ $log->user_agent }}">
                                        {{ Str::limit($log->user_agent ?? '未知客户端', 28) }}
                                    </div>
                                </td>

                                <!-- 审计明细操作 -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                    <button type="button" class="px-2.5 py-1 rounded-md border border-stone-200/80 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-100 dark:hover:bg-stone-800 text-xs transition-colors" data-hs-overlay="#hs-modal-audit-details-{{ $log->id }}">
                                        查看详情
                                    </button>
                                </td>
                            </tr>

                            <!-- 审计日志详情 Modal -->
                            <x-admin::modal id="hs-modal-audit-details-{{ $log->id }}" :title="$log->action_label" :subtitle="'ID #' . $log->id . ' · ' . $log->created_at?->format('Y-m-d H:i:s')" icon="bi-file-earmark-text" maxWidth="max-w-lg">
                                <div class="p-6 space-y-4 text-xs">
                                    <!-- 动作说明 -->
                                    <div class="p-3 bg-stone-50 dark:bg-stone-900/50 rounded-xl border border-stone-200/60 dark:border-stone-800/60 space-y-1">
                                        <div class="text-[11px] font-medium text-stone-500 dark:text-stone-400">操作释义</div>
                                        <div class="text-stone-800 dark:text-stone-200 font-medium">{{ $log->action_description }}</div>
                                        <div class="text-[10px] text-stone-400 font-mono">底层标识: {{ $log->action }}</div>
                                    </div>

                                    <!-- 元信息两列 -->
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div class="space-y-0.5">
                                            <div class="text-stone-400 text-[11px]">操作执行人</div>
                                            <div class="font-medium text-stone-900 dark:text-stone-100">
                                                {{ $log->user?->name ?? '系统访客 / CLI' }}
                                            </div>
                                            <div class="text-stone-400 font-mono text-[10px]">{{ $log->user?->email ?? '-' }}</div>
                                        </div>
                                        <div class="space-y-0.5">
                                            <div class="text-stone-400 text-[11px]">网络来源 IP</div>
                                            <div class="font-mono text-stone-900 dark:text-stone-100">
                                                {{ $log->ip_address ?? '127.0.0.1' }}
                                            </div>
                                        </div>
                                    </div>

                                    @if($log->user_agent)
                                        <div class="space-y-1">
                                            <div class="text-stone-400 text-[11px]">客户端环境 (User Agent)</div>
                                            <div class="p-2.5 bg-stone-50 dark:bg-stone-900/60 rounded-lg text-[11px] font-mono text-stone-600 dark:text-stone-300 break-all border border-stone-200/60 dark:border-stone-800/60">
                                                {{ $log->user_agent }}
                                            </div>
                                        </div>
                                    @endif

                                    <!-- 脱敏上下文业务数据 Details (JSON 树) -->
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="text-stone-400 text-[11px]">脱敏业务明细载荷 (Payload)</div>
                                            <span class="text-[10px] text-emerald-600 font-mono">已强制脱敏</span>
                                        </div>
                                        <pre class="p-3 bg-stone-900 text-stone-100 rounded-xl text-[11px] font-mono overflow-x-auto max-h-56 leading-relaxed"><code>{{ json_encode($log->details ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                </div>

                                <x-slot:footer>
                                    <button type="button" class="px-4 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs" data-hs-overlay="#hs-modal-audit-details-{{ $log->id }}">
                                        关闭详情
                                    </button>
                                </x-slot:footer>
                            </x-admin::modal>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-stone-400 dark:text-stone-500 font-mono">
                                    暂无符合筛选条件的操作审计记录
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 分页与容量控制栏 (支持翻页与 5/10/15/20/50 条每页切换) -->
            <div class="p-4 px-6 border-t border-stone-200/60 dark:border-stone-800 bg-stone-50/40 dark:bg-stone-900/20 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <!-- 左侧统计 -->
                <div class="flex items-center gap-3 text-stone-400 dark:text-stone-500 font-mono text-[11px]">
                    <span>显示 {{ $logs->firstItem() ?? 0 }} 至 {{ $logs->lastItem() ?? 0 }} 条 · 共 {{ $logs->total() }} 条记录</span>
                    <span class="text-stone-300 dark:text-stone-700">|</span>
                    <span>第 {{ $logs->currentPage() }} / {{ $logs->lastPage() }} 页</span>
                </div>

                <!-- 中间容量选择器 -->
                <div class="flex items-center gap-2 text-[11px] font-mono text-stone-400">
                    <span>每页显示:</span>
                    <div class="inline-flex items-center p-0.5 rounded-lg bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700">
                        @foreach([10, 15, 20, 50] as $size)
                            <a href="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" class="px-2 py-0.5 rounded-md transition-colors {{ (int) request('per_page', 15) === $size ? 'bg-white dark:bg-[#202020] text-stone-900 dark:text-stone-100 shadow-2xs font-semibold' : 'text-stone-400 hover:text-stone-700 dark:hover:text-stone-300' }}">
                                {{ $size }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- 右侧翻页按钮 -->
                <div>
                    @if($logs->hasPages())
                        {{ $logs->links('adminflow-core::pagination.nordic') }}
                    @else
                        <span class="text-[11px] text-stone-400 font-mono">已加载单页全部记录</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
