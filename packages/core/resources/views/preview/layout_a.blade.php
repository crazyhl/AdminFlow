@extends('adminflow-core::layouts.admin')

@section('title', '方案 A 效果预览 · 流式全宽 + 表格饱满')
@section('breadcrumb', '布局选型 · 方案 A (流式全宽)')

@section('content')
    <!-- 顶部方案对比切换工具条 (仅用于选型测试) -->
    <div class="p-3.5 bg-stone-200/50 dark:bg-stone-800/80 rounded-xl border border-stone-300/60 dark:border-stone-700/60 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-semibold bg-stone-900 text-stone-100 dark:bg-stone-100 dark:text-stone-900 text-[11px]">
                当前方案 A
            </span>
            <span class="font-medium text-stone-800 dark:text-stone-200">
                流式全宽展开 + 表格饱满锚定（解除 1024px 宽度限制，大屏横向自然拉伸）
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.preview.layout-b') }}" class="px-3 py-1.5 rounded-lg bg-white dark:bg-[#202020] text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 border border-stone-200 dark:border-stone-700 font-medium transition-colors">
                切换至方案 B (双栏工作台) →
            </a>
            <a href="{{ route('admin.users.index') }}" class="px-2.5 py-1.5 rounded-lg text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 transition-colors">
                返回正式页
            </a>
        </div>
    </div>

    <!-- 方案 A 主体结构：w-full 全宽流式展开 -->
    <div class="w-full space-y-6">
        <!-- 页面标题与新增按钮 -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-serif-nordic font-semibold text-stone-900 dark:text-stone-100 tracking-tight">
                    成员与权限管理
                </h1>
                <p class="text-xs text-stone-500 dark:text-stone-400">
                    管理系统协同成员、配置预设角色与启停访问权限 · 宽屏全域拉伸，消除右侧断崖空白。
                </p>
            </div>

            <button type="button" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
                <i class="bi bi-plus-lg text-xs"></i>
                <span>添加成员</span>
            </button>
        </div>

        <!-- 扁平微指标卡（全宽自适应，横向分布） -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-5 border-y border-stone-200/60 dark:border-stone-800">
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">成员总数</div>
                <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                    {{ $users->total() }}
                </div>
                <div class="text-[11px] text-stone-400 mt-1 font-mono">系统授权基座</div>
            </div>
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">正常活跃状态</div>
                <div class="text-3xl font-serif-nordic font-bold text-emerald-700 dark:text-emerald-400 mt-1">
                    {{ \AdminFlow\Core\Models\User::where('is_active', true)->count() }}
                </div>
                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 font-mono">全部正常登录中</div>
            </div>
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">超级管理员</div>
                <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                    {{ \AdminFlow\Core\Models\User::where('role', 'super_admin')->count() }}
                </div>
                <div class="text-[11px] text-stone-400 mt-1 font-mono">核心管控特权</div>
            </div>
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">已启用双因素/审计</div>
                <div class="text-3xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-1">
                    100%
                </div>
                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1 font-mono">全量操作审计记录</div>
            </div>
        </div>

        <!-- 搜索与筛选器 (水平舒展布局) -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 min-w-[280px]">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3 text-stone-400">
                        <i class="bi bi-search text-xs"></i>
                    </div>
                    <input type="text" placeholder="搜索成员姓名、登录邮箱、关联角色..." class="py-2 ps-8 pe-3 block w-full bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 placeholder:text-stone-400 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors shadow-2xs">
                </div>

                <select class="py-2 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors shadow-2xs">
                    <option value="">全部预设角色 (Super Admin / Editor / Viewer)</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>

                <select class="py-2 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors shadow-2xs">
                    <option value="">全部状态 (正常 / 停用)</option>
                    <option value="1">正常活跃</option>
                    <option value="0">已停用</option>
                </select>

                <button type="button" class="px-3.5 py-2 bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-lg text-xs font-medium transition-colors">
                    应用筛选
                </button>
            </div>

            <div class="text-xs text-stone-400 font-mono">
                实时匹配: {{ $users->total() }} 条结果
            </div>
        </div>

        <!-- 方案 A 饱满表格卡片 (min-h-[460px] 弹性撑起 + 底部锚定条) -->
        <div class="bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 shadow-[0_1px_3px_rgba(0,0,0,0.02)] min-h-[460px] flex flex-col justify-between overflow-hidden transition-colors duration-150">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300 divide-y divide-stone-100 dark:divide-stone-800">
                    <thead class="bg-stone-50/60 dark:bg-stone-900/40 text-[11px] font-medium text-stone-400 dark:text-stone-500 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3.5 px-6">成员基本信息</th>
                            <th scope="col" class="py-3.5 px-6">角色权限</th>
                            <th scope="col" class="py-3.5 px-6">账号状态</th>
                            <th scope="col" class="py-3.5 px-6">安全校验依据</th>
                            <th scope="col" class="py-3.5 px-6">创建时间</th>
                            <th scope="col" class="py-3.5 px-6 text-right">管理操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800/80">
                        @foreach($users as $user)
                            <tr class="hover:bg-stone-50/40 dark:hover:bg-stone-800/30 transition-colors">
                                <!-- 成员信息 -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-9 h-9 rounded-full bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-stone-900 dark:text-stone-100 flex items-center gap-2">
                                                <span class="text-sm font-semibold">{{ $user->name }}</span>
                                                @if(auth('admin')->id() === $user->id)
                                                    <span class="text-[10px] bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60 px-1.5 py-0.2 rounded font-mono">当前会话</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-stone-400 font-mono mt-0.5">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 角色分配 -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    @if($user->isSuperAdmin())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-medium bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900">
                                            {{ $user->role->label() }}
                                        </span>
                                    @elseif($user->role === \AdminFlow\Core\Enums\Role::EDITOR)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-medium bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                            {{ $user->role->label() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                                            {{ $user->role->label() }}
                                        </span>
                                    @endif
                                </td>

                                <!-- 账号状态 -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center gap-2 text-emerald-700 dark:text-emerald-400 text-xs font-medium">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>正常允许登录</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 text-stone-400 dark:text-stone-500 text-xs font-medium">
                                            <span class="w-2 h-2 rounded-full bg-stone-400"></span>
                                            <span>已全局停用</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- 安全与策略说明 -->
                                <td class="py-4 px-6 whitespace-nowrap text-stone-500 dark:text-stone-400 text-xs">
                                    <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                        <i class="bi bi-shield-check text-emerald-600"></i>
                                        <span>服务端 Policy 强制授权</span>
                                    </div>
                                </td>

                                <!-- 创建时间 -->
                                <td class="py-4 px-6 whitespace-nowrap text-stone-400 dark:text-stone-500 font-mono text-[11px]">
                                    {{ $user->created_at?->format('Y-m-d H:i:s') ?? '-' }}
                                </td>

                                <!-- 操作项 -->
                                <td class="py-4 px-6 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" class="p-1.5 px-2.5 rounded-lg border border-stone-200/80 dark:border-stone-800 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-700 dark:text-stone-300 transition-colors" title="编辑资料">
                                            <i class="bi bi-pencil-square text-xs"></i>
                                            <span class="ms-1">编辑</span>
                                        </button>
                                        <button type="button" class="p-1.5 px-2.5 rounded-lg border border-stone-200/80 dark:border-stone-800 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-700 dark:text-stone-300 transition-colors" title="重置密码">
                                            <i class="bi bi-key text-xs"></i>
                                            <span class="ms-1">密码</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- 卡片底部饱满锚定栏 (即使只有 1 条数据，卡片也不会突兀坍缩) -->
            <div class="p-4 px-6 border-t border-stone-200/60 dark:border-stone-800 bg-stone-50/40 dark:bg-stone-900/20 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3 text-stone-400 dark:text-stone-500 font-mono text-[11px]">
                    <span>显示 1 至 {{ $users->count() }} 项 · 共 {{ $users->total() }} 名系统成员</span>
                    <span class="text-stone-300 dark:text-stone-700">|</span>
                    <span class="text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                        <i class="bi bi-check-circle"></i> 数据同步正常
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-stone-400">单页上限 15 条 · 自动分页</span>
                </div>
            </div>
        </div>
    </div>
@endsection
