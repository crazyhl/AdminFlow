@extends('adminflow-core::layouts.admin')

@section('title', '成员列表与授权')
@section('breadcrumb', '成员列表与授权')

@section('content')
    <!-- 主体双栏工作台布局 (12 列网格：8 列核心列表与操作 + 4 列右侧权限规则与安全字典) -->
    <div class="w-full grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
        <!-- 左侧 8 列：成员核心数据管理区 -->
        <div class="xl:col-span-8 space-y-6">
            <!-- 标题与新增按钮 -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-serif-nordic font-semibold text-stone-900 dark:text-stone-100 tracking-tight">
                        成员与权限管理
                    </h1>
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        管理系统协同成员、配置预设角色与启停访问权限 · 服务端策略严格校验。
                    </p>
                </div>

                @can('create', \AdminFlow\Core\Models\User::class)
                    <div>
                        <button type="button" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs" data-hs-overlay="#hs-modal-create-user">
                            <i class="bi bi-plus-lg text-xs"></i>
                            <span>添加成员</span>
                        </button>
                    </div>
                @endcan
            </div>

            <!-- 极简微统计卡 (Nordic Minimal 排印) -->
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
                        {{ \AdminFlow\Core\Models\User::query()->where('is_active', true)->count() }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-stone-400 dark:text-stone-500">超级管理员</div>
                    <div class="text-2xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-0.5">
                        {{ \AdminFlow\Core\Models\User::query()->where('role', 'super_admin')->count() }}
                    </div>
                </div>
            </div>

            <!-- 搜索与筛选表单 -->
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3">
                <!-- 搜索输入框 -->
                <div class="relative flex-1 min-w-[200px]">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3 text-stone-400">
                        <i class="bi bi-search text-xs"></i>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="搜索成员姓名、登录邮箱..." class="py-1.5 ps-8 pe-3 block w-full bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 placeholder:text-stone-400 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                </div>

                <!-- 角色筛选 -->
                <select name="role" class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                    <option value="">全部预设角色</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ ($filters['role'] ?? '') === $role->value ? 'selected' : '' }}>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>

                <!-- 状态筛选 -->
                <select name="is_active" class="py-1.5 px-3 bg-white dark:bg-[#202020] border border-stone-200/80 dark:border-stone-800 rounded-lg text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:border-stone-400 dark:focus:border-stone-600 transition-colors">
                    <option value="">全部状态</option>
                    <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>启用</option>
                    <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>停用</option>
                </select>

                <button type="submit" class="px-3 py-1.5 bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-700 rounded-lg text-xs font-medium transition-colors">
                    筛选
                </button>

                @if(!empty($filters['search']) || !empty($filters['role']) || ($filters['is_active'] ?? '') !== '')
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                        重置
                    </a>
                @endif
            </form>

            <!-- 成员数据表格卡片 -->
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
                            @forelse($users as $user)
                                <tr class="hover:bg-stone-50/40 dark:hover:bg-stone-800/30 transition-colors">
                                    <!-- 成员信息 -->
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

                                    <!-- 角色分配 -->
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

                                    <!-- 账号状态 -->
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

                                    <!-- 创建时间 -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-stone-400 dark:text-stone-500 font-mono text-[11px]">
                                        {{ $user->created_at?->format('Y-m-d H:i') ?? '-' }}
                                    </td>

                                    <!-- 操作项 -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <!-- 编辑按钮 -->
                                            @can('update', $user)
                                                <button type="button" class="p-1 px-2 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" data-hs-overlay="#hs-modal-edit-user-{{ $user->id }}" title="编辑成员">
                                                    <i class="bi bi-pencil-square text-xs"></i>
                                                </button>

                                                <button type="button" class="p-1 px-2 rounded hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" data-hs-overlay="#hs-modal-reset-password-{{ $user->id }}" title="重置密码">
                                                    <i class="bi bi-key text-xs"></i>
                                                </button>
                                            @endcan

                                            <!-- 启停状态切换 -->
                                            @can('toggleStatus', $user)
                                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-1 px-2 rounded hover:bg-stone-100 dark:hover:bg-stone-800 {{ $user->is_active ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }} transition-colors" title="{{ $user->is_active ? '停用账号' : '启用账号' }}" onclick="return confirm('确认要{{ $user->is_active ? '停用' : '启用' }}成员「{{ $user->name }}」吗？')">
                                                        <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} text-xs"></i>
                                                    </button>
                                                </form>
                                            @endcan

                                            <!-- 删除账号 -->
                                            @can('delete', $user)
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 px-2 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 transition-colors" title="删除账号" onclick="return confirm('警告：确认要永久删除成员「{{ $user->name }}」吗？此操作无法撤销。')">
                                                        <i class="bi bi-trash text-xs"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                <!-- 编辑成员 Modal -->
                                @can('update', $user)
                                    <div id="hs-modal-edit-user-{{ $user->id }}" class="hs-overlay hidden fixed inset-0 z-70 overflow-x-hidden overflow-y-auto bg-stone-900/40 backdrop-blur-xs flex items-center justify-center p-4">
                                        <div class="hs-overlay-open:opacity-100 hs-overlay-open:scale-100 opacity-0 scale-95 transition-all duration-200 w-full max-w-md bg-white dark:bg-[#202020] rounded-xl border border-stone-200/80 dark:border-stone-800 shadow-xl overflow-hidden">
                                            <div class="px-5 py-4 border-b border-stone-200/60 dark:border-stone-800 flex items-center justify-between">
                                                <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">
                                                    编辑成员「{{ $user->name }}」
                                                </h3>
                                                <button type="button" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200" data-hs-overlay="#hs-modal-edit-user-{{ $user->id }}">
                                                    <i class="bi bi-x-lg text-xs"></i>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-5 space-y-4 text-xs">
                                                @csrf
                                                @method('PUT')
                                                <div>
                                                    <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">成员姓名</label>
                                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                                                </div>
                                                <div>
                                                    <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">登录邮箱</label>
                                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                                                </div>
                                                <div>
                                                    <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">预设角色分配</label>
                                                    <select name="role" required class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>
                                                                {{ $role->label() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="flex justify-end gap-2 pt-2">
                                                    <button type="button" class="px-3.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" data-hs-overlay="#hs-modal-edit-user-{{ $user->id }}">
                                                        取消
                                                    </button>
                                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 font-medium hover:bg-stone-800 dark:hover:bg-stone-200">
                                                        保存变更
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- 重置密码 Modal -->
                                    <div id="hs-modal-reset-password-{{ $user->id }}" class="hs-overlay hidden fixed inset-0 z-70 overflow-x-hidden overflow-y-auto bg-stone-900/40 backdrop-blur-xs flex items-center justify-center p-4">
                                        <div class="hs-overlay-open:opacity-100 hs-overlay-open:scale-100 opacity-0 scale-95 transition-all duration-200 w-full max-w-md bg-white dark:bg-[#202020] rounded-xl border border-stone-200/80 dark:border-stone-800 shadow-xl overflow-hidden">
                                            <div class="px-5 py-4 border-b border-stone-200/60 dark:border-stone-800 flex items-center justify-between">
                                                <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">
                                                    重置密码 · {{ $user->name }}
                                                </h3>
                                                <button type="button" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200" data-hs-overlay="#hs-modal-reset-password-{{ $user->id }}">
                                                    <i class="bi bi-x-lg text-xs"></i>
                                                </button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="p-5 space-y-4 text-xs">
                                                @csrf
                                                <div>
                                                    <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">新密码</label>
                                                    <input type="password" name="password" required minlength="8" placeholder="至少 8 位，包含英文字母与数字" class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                                                </div>
                                                <div>
                                                    <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">确认新密码</label>
                                                    <input type="password" name="password_confirmation" required minlength="8" placeholder="再次输入新密码" class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                                                </div>
                                                <div class="flex justify-end gap-2 pt-2">
                                                    <button type="button" class="px-3.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" data-hs-overlay="#hs-modal-reset-password-{{ $user->id }}">
                                                        取消
                                                    </button>
                                                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 font-medium hover:bg-stone-800 dark:hover:bg-stone-200">
                                                        确认重置
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endcan
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-stone-400 dark:text-stone-500 font-mono">
                                        未查找到符合条件的成员记录
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- 分页组件 -->
                <div class="p-3.5 px-4 border-t border-stone-200/60 dark:border-stone-800 flex items-center justify-between text-xs text-stone-400 font-mono text-[11px]">
                    <span>共 {{ $users->total() }} 名系统成员</span>
                    @if($users->hasPages())
                        <div>{{ $users->links() }}</div>
                    @else
                        <span>状态正常 · 实时同步</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 右侧 4 列：常驻权限规则字典与安全控制规范 (彻底消除大屏断崖留白) -->
        <div class="xl:col-span-4 space-y-6">
            <!-- 预设角色与权限范围字典 -->
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

            <!-- 安全控制与防自损规范 -->
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

    <!-- 添加成员 Modal -->
    @can('create', \AdminFlow\Core\Models\User::class)
        <div id="hs-modal-create-user" class="hs-overlay hidden fixed inset-0 z-70 overflow-x-hidden overflow-y-auto bg-stone-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="hs-overlay-open:opacity-100 hs-overlay-open:scale-100 opacity-0 scale-95 transition-all duration-200 w-full max-w-md bg-white dark:bg-[#202020] rounded-xl border border-stone-200/80 dark:border-stone-800 shadow-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-stone-200/60 dark:border-stone-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">
                        添加新成员
                    </h3>
                    <button type="button" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200" data-hs-overlay="#hs-modal-create-user">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="p-5 space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">成员姓名 <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="输入成员姓名" class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                    </div>
                    <div>
                        <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">登录邮箱 <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="user@example.com" class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                    </div>
                    <div>
                        <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">初始登录密码 <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="至少 8 位，包含英文字母与数字" class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                    </div>
                    <div>
                        <label class="block font-medium text-stone-700 dark:text-stone-300 mb-1">预设角色分配 <span class="text-rose-500">*</span></label>
                        <select name="role" required class="w-full px-3 py-2 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 rounded-lg text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:border-stone-400">
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>
                                    {{ $role->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="create-is-active" name="is_active" value="1" checked class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                        <label for="create-is-active" class="text-stone-700 dark:text-stone-300">创建后立即允许登录</label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-3.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800" data-hs-overlay="#hs-modal-create-user">
                            取消
                        </button>
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 font-medium hover:bg-stone-800 dark:hover:bg-stone-200">
                            立即创建
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
