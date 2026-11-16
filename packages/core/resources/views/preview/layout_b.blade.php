@extends('adminflow-core::layouts.admin')

@section('title', '方案 B 效果预览 · 双栏工作台 + 规则面板')
@section('breadcrumb', '布局选型 · 方案 B (双栏工作台)')

@section('content')
    <!-- 顶部方案对比切换工具条 (仅用于选型测试) -->
    <div class="p-3.5 bg-stone-200/50 dark:bg-stone-800/80 rounded-xl border border-stone-300/60 dark:border-stone-700/60 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-semibold bg-stone-900 text-stone-100 dark:bg-stone-100 dark:text-stone-900 text-[11px]">
                当前方案 B
            </span>
            <span class="font-medium text-stone-800 dark:text-stone-200">
                双栏工作台布局（左侧数据列表 + 右侧常驻预设权限规则与安全审计面板，化空白为生产力）
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.preview.layout-a') }}" class="px-3 py-1.5 rounded-lg bg-white dark:bg-[#202020] text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 border border-stone-200 dark:border-stone-700 font-medium transition-colors">
                ← 切换至方案 A (流式全宽)
            </a>
            <a href="{{ route('admin.users.index') }}" class="px-2.5 py-1.5 rounded-lg text-stone-500 hover:text-stone-800 dark:hover:text-stone-200 transition-colors">
                返回正式页
            </a>
        </div>
    </div>

    <!-- 方案 B 主体结构：双栏网格 (12 列网格：8 列数据主区 + 4 列右侧工作台辅助面板) -->
    <div class="w-full grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
        <!-- 左侧 8 列：成员核心数据工作区 -->
        <div class="xl:col-span-8 space-y-6">
            <!-- 标题区 -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-serif-nordic font-semibold text-stone-900 dark:text-stone-100 tracking-tight">
                        成员与权限管理
                    </h1>
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        核心协作者名单与即时操作面板 · 配合右侧策略字典协同管控。
                    </p>
                </div>

                <button type="button" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
                    <i class="bi bi-plus-lg text-xs"></i>
                    <span>添加成员</span>
                </button>
            </div>

            <!-- 扁平微指标卡 -->
            <div class="grid grid-cols-3 gap-6 py-4 border-y border-stone-200/60 dark:border-stone-800">
                <div>
                    <div class="text-xs text-stone-400 dark:text-stone-500">成员总数</div>
                    <div class="text-2xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-0.5">
                        {{ $users->total() }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-stone-400 dark:text-stone-500">正常活跃</div>
                    <div class="text-2xl font-serif-nordic font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">
                        {{ \AdminFlow\Core\Models\User::where('is_active', true)->count() }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-stone-400 dark:text-stone-500">超级管理员</div>
                    <div class="text-2xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-0.5">
                        {{ \AdminFlow\Core\Models\User::where('role', 'super_admin')->count() }}
                    </div>
                </div>
            </div>

            <!-- 搜索筛选区 -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3 text-stone-400">
                        <i class="bi bi-search text-xs"></i>
                    </div>
                    <input type="text" placeholder="搜索成员姓名、登录邮箱..." class="py-1.5 ps-8 pe-3 block w-full bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 placeholder:text-stone-400 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                </div>

                <select class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                    <option value="">全部预设角色</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>

                <select class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                    <option value="">全部状态</option>
                    <option value="1">正常</option>
                    <option value="0">停用</option>
                </select>

                <button type="button" class="px-3 py-1.5 bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-lg text-xs font-medium transition-colors">
                    筛选
                </button>
            </div>

            <!-- 数据表格卡片 -->
            <div class="bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 shadow-[0_1px_3px_rgba(0,0,0,0.02)] overflow-hidden transition-colors duration-150">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300 divide-y divide-stone-100 dark:divide-stone-800">
                        <thead class="bg-stone-50/60 dark:bg-stone-900/40 text-[11px] font-medium text-stone-400 dark:text-stone-500 uppercase tracking-wider">
                            <tr>
                                <th scope="col" class="py-3 px-4">成员信息</th>
                                <th scope="col" class="py-3 px-4">角色分配</th>
                                <th scope="col" class="py-3 px-4">账号状态</th>
                                <th scope="col" class="py-3 px-4">创建时间</th>
                                <th scope="col" class="py-3 px-4 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800/80">
                            @foreach($users as $user)
                                <tr class="hover:bg-stone-50/40 dark:hover:bg-stone-800/30 transition-colors">
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 flex items-center justify-center font-bold text-xs shrink-0 border border-stone-200/60 dark:border-stone-700">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-stone-900 dark:text-stone-100 flex items-center gap-1.5">
                                                    <span>{{ $user->name }}</span>
                                                    @if(auth('admin')->id() === $user->id)
                                                        <span class="text-[10px] bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/60 px-1 rounded">当前登录</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-stone-400 font-mono">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($user->isSuperAdmin())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900">
                                                {{ $user->role->label() }}
                                            </span>
                                        @elseif($user->role === \AdminFlow\Core\Enums\Role::EDITOR)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                                {{ $user->role->label() }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                                                {{ $user->role->label() }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($user->is_active)
                                            <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 text-xs">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>正常</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-stone-400 dark:text-stone-500 text-xs">
                                                <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                                <span>已停用</span>
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap text-stone-400 dark:text-stone-500 font-mono text-[11px]">
                                        {{ $user->created_at?->format('Y-m-d H:i') ?? '-' }}
                                    </td>

                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="p-1 px-2 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" title="编辑成员">
                                                <i class="bi bi-pencil-square text-xs"></i>
                                            </button>
                                            <button type="button" class="p-1 px-2 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" title="重置密码">
                                                <i class="bi bi-key text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3.5 px-4 border-t border-stone-200/60 dark:border-stone-800 flex items-center justify-between text-xs text-stone-400 font-mono text-[11px]">
                    <span>共 {{ $users->total() }} 名成员</span>
                    <span>状态正常</span>
                </div>
            </div>
        </div>

        <!-- 右侧 4 列：常驻权限规则说明与安全状态面板 (彻底消化右侧留白) -->
        <div class="xl:col-span-4 space-y-6">
            <!-- 卡片 1：预设角色权限字典卡片 -->
            <div class="bg-white dark:bg-[#202020] p-5 rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-4 shadow-[0_1px_3px_rgba(0,0,0,0.02)] transition-colors duration-150">
                <div class="flex items-center justify-between border-b border-stone-200/60 dark:border-stone-800 pb-3">
                    <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 flex items-center gap-2">
                        <i class="bi bi-shield-shaded text-sm text-stone-700 dark:text-stone-300"></i>
                        <span>预设角色与权限字典</span>
                    </div>
                    <span class="text-[10px] bg-stone-100 dark:bg-stone-800 text-stone-500 px-1.5 py-0.5 rounded font-mono">严格守卫</span>
                </div>

                <div class="space-y-3.5 text-xs">
                    <!-- 超级管理员 -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-stone-900 dark:text-stone-100 text-[11px]">超级管理员 (Super Admin)</span>
                            <span class="text-[10px] text-stone-400 font-mono">全权放行</span>
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                            拥有底座所有管理权、用户启停/重置、系统配置、审计日志与 CMS 全量发布特权；具备免拦截防御。
                        </p>
                    </div>

                    <div class="h-px bg-stone-100 dark:bg-stone-800"></div>

                    <!-- 内容编辑员 -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-amber-800 dark:text-amber-300 text-[11px]">内容编辑员 (Editor)</span>
                            <span class="text-[10px] text-amber-600 font-mono">CMS 核心</span>
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                            专用于创作工坊：文章创建/编辑/草稿发布、媒体图床上传管理、访客评论审核；不可管理系统用户与底层安全。
                        </p>
                    </div>

                    <div class="h-px bg-stone-100 dark:bg-stone-800"></div>

                    <!-- 只读访客 -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-stone-700 dark:text-stone-300 text-[11px]">只读访客 (Viewer)</span>
                            <span class="text-[10px] text-stone-400 font-mono">受限只读</span>
                        </div>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                            仅能查看仪表盘概要和已公开内容数据，无任何写入或状态变更权限。
                        </p>
                    </div>
                </div>
            </div>

            <!-- 卡片 2：安全防御与审计准则 -->
            <div class="bg-[#f7f6f3] dark:bg-[#202020] p-5 rounded-xl border border-stone-200/70 dark:border-stone-800 space-y-3.5 text-xs transition-colors duration-150">
                <div class="text-xs font-semibold text-stone-900 dark:text-stone-100 flex items-center gap-2">
                    <i class="bi bi-lock text-sm text-stone-600 dark:text-stone-400"></i>
                    <span>安全控制与防自损规范</span>
                </div>
                <div class="space-y-2 text-[11px] text-stone-600 dark:text-stone-400 leading-relaxed">
                    <div class="flex items-start gap-2">
                        <i class="bi bi-check2 text-emerald-600 shrink-0 mt-0.5"></i>
                        <span><strong>防自我停用</strong>：操作者无论权限多高，绝对禁止停用当前登录账号。</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="bi bi-check2 text-emerald-600 shrink-0 mt-0.5"></i>
                        <span><strong>防自删与防删超管</strong>：超级管理员账号受核心 Policy 硬保护，防止系统失联。</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="bi bi-check2 text-emerald-600 shrink-0 mt-0.5"></i>
                        <span><strong>敏感参数脱敏</strong>：重置密码与修改操作在审计日志中自动脱敏，不存明文。</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
