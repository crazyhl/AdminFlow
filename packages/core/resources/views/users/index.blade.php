@extends('adminflow-core::layouts.admin')

@section('title', '成员列表与授权')
@section('breadcrumb', '成员列表与授权')

@section('content')
    @php
        $currentLayout = request('layout', 'b');
    @endphp

    <!-- 主体工作台布局 (支持在方案 A 全宽与方案 B 双栏之间实时切换) -->
    <div class="w-full grid grid-cols-1 {{ $currentLayout === 'b' ? 'xl:grid-cols-12' : '' }} gap-8 items-start">
        <!-- 核心数据管理区：方案 B 下占 8 列，方案 A 下全宽 12 列展开 -->
        <div class="{{ $currentLayout === 'b' ? 'xl:col-span-8' : 'w-full' }} space-y-6">
            <!-- 标题、布局切换胶囊与新增按钮 -->
            <x-admin::page-header title="成员与权限管理" description="管理系统协同成员、配置预设角色与启停访问权限 · 点击上方胶囊可即时切换排版形态。">
                <x-slot:actions>
                    <!-- 方案切换胶囊 -->
                    <div class="inline-flex items-center p-0.5 rounded-lg bg-stone-200/60 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-[11px] font-mono">
                        <a href="{{ request()->fullUrlWithQuery(['layout' => 'b']) }}" class="px-2 py-0.5 rounded-md transition-colors {{ $currentLayout === 'b' ? 'bg-white dark:bg-[#202020] text-stone-900 dark:text-stone-100 shadow-2xs font-semibold' : 'text-stone-400 hover:text-stone-700 dark:hover:text-stone-300' }}" title="切换至双栏工作台布局">
                            方案 B (双栏工作台)
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['layout' => 'a']) }}" class="px-2 py-0.5 rounded-md transition-colors {{ $currentLayout === 'a' ? 'bg-white dark:bg-[#202020] text-stone-900 dark:text-stone-100 shadow-2xs font-semibold' : 'text-stone-400 hover:text-stone-700 dark:hover:text-stone-300' }}" title="切换至流式全宽布局">
                            方案 A (流式全宽)
                        </a>
                    </div>

                    @can('create', \AdminFlow\Core\Models\User::class)
                        <button type="button" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs" data-hs-overlay="#hs-modal-create-user">
                            <i class="bi bi-plus-lg text-xs"></i>
                            <span>添加成员</span>
                        </button>
                    @endcan
                </x-slot:actions>
            </x-admin::page-header>

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
            <div class="bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 shadow-[0_1px_3px_rgba(0,0,0,0.02)] overflow-hidden transition-colors duration-150 {{ $currentLayout === 'a' ? 'min-h-[460px] flex flex-col justify-between' : '' }}">
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

                                    <!-- 操作项 (完全使用自定义高保真 Modal，告别原生丑陋 confirm) -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- 编辑按钮 -->
                                            @can('update', $user)
                                                <button type="button" class="p-1 px-2 rounded-md hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" data-hs-overlay="#hs-modal-edit-user-{{ $user->id }}" title="编辑成员资料">
                                                    <i class="bi bi-pencil-square text-xs"></i>
                                                </button>

                                                <button type="button" class="p-1 px-2 rounded-md hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" data-hs-overlay="#hs-modal-reset-password-{{ $user->id }}" title="重置登录密码">
                                                    <i class="bi bi-key text-xs"></i>
                                                </button>
                                            @endcan

                                            <!-- 启停状态切换 (触发高保真确认 Modal) -->
                                            @can('toggleStatus', $user)
                                                <button type="button" class="p-1 px-2 rounded-md hover:bg-stone-100 dark:hover:bg-stone-800 {{ $user->is_active ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }} transition-colors" data-hs-overlay="#hs-modal-confirm-toggle-{{ $user->id }}" title="{{ $user->is_active ? '停用账号' : '启用账号' }}">
                                                    <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} text-xs"></i>
                                                </button>
                                            @endcan

                                            <!-- 删除账号 (触发高保真危险确认 Modal) -->
                                            @can('delete', $user)
                                                <button type="button" class="p-1 px-2 rounded-md hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 transition-colors" data-hs-overlay="#hs-modal-confirm-delete-{{ $user->id }}" title="删除成员账号">
                                                    <i class="bi bi-trash text-xs"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                @can('update', $user)
                                    <!-- 编辑成员 Modal -->
                                    <x-admin::modal id="hs-modal-edit-user-{{ $user->id }}" title="编辑成员资料" subtitle="更新成员姓名、登录邮箱与分配的系统角色" icon="bi-person-gear">
                                        <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="p-6 space-y-4 text-xs">
                                                <div>
                                                    <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">成员姓名 <span class="text-rose-500">*</span></label>
                                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">登录邮箱 <span class="text-rose-500">*</span></label>
                                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">预设角色分配 <span class="text-rose-500">*</span></label>
                                                    <select name="role" required class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>
                                                                {{ $role->label() }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="px-6 py-3.5 bg-stone-50/60 dark:bg-stone-900/40 border-t border-stone-200/60 dark:border-stone-800/80 flex items-center justify-end gap-2.5">
                                                <button type="button" class="px-3.5 py-2 rounded-lg text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-200/50 dark:hover:bg-stone-800 transition-colors" data-hs-overlay="#hs-modal-edit-user-{{ $user->id }}">
                                                    取消
                                                </button>
                                                <button type="submit" class="px-4 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
                                                    保存变更
                                                </button>
                                            </div>
                                        </form>
                                    </x-admin::modal>

                                    <!-- 重置密码 Modal -->
                                    <x-admin::modal id="hs-modal-reset-password-{{ $user->id }}" title="重置登录密码" :subtitle="'正在为成员「' . $user->name . '」更新访问口令'" icon="bi-key">
                                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                                            @csrf
                                            <div class="p-6 space-y-4 text-xs">
                                                <div>
                                                    <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">新密码 <span class="text-rose-500">*</span></label>
                                                    <input type="password" name="password" required minlength="8" placeholder="至少 8 位，须包含字母与数字" class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">确认新密码 <span class="text-rose-500">*</span></label>
                                                    <input type="password" name="password_confirmation" required minlength="8" placeholder="再次输入新密码核对" class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                                                </div>
                                                <div class="p-2.5 rounded-lg bg-stone-100/60 dark:bg-stone-800/40 border border-stone-200/60 dark:border-stone-700/50 flex items-center gap-2 text-[11px] text-stone-500 font-mono">
                                                    <i class="bi bi-shield-check text-emerald-600"></i>
                                                    <span>密码修改操作将在审计日志中记录，明文密码强制脱敏</span>
                                                </div>
                                            </div>

                                            <div class="px-6 py-3.5 bg-stone-50/60 dark:bg-stone-900/40 border-t border-stone-200/60 dark:border-stone-800/80 flex items-center justify-end gap-2.5">
                                                <button type="button" class="px-3.5 py-2 rounded-lg text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-200/50 dark:hover:bg-stone-800 transition-colors" data-hs-overlay="#hs-modal-reset-password-{{ $user->id }}">
                                                    取消
                                                </button>
                                                <button type="submit" class="px-4 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
                                                    确认重置
                                                </button>
                                            </div>
                                        </form>
                                    </x-admin::modal>
                                @endcan

                                <!-- 启停状态二次确认 -->
                                @can('toggleStatus', $user)
                                    <x-admin::confirm-dialog
                                        id="hs-modal-confirm-toggle-{{ $user->id }}"
                                        :action="route('admin.users.toggle-active', $user)"
                                        :title="'确认' . ($user->is_active ? '停用' : '启用') . '成员账号？'"
                                        :message="'您正在操作成员「<strong>' . e($user->name) . '</strong>」。' . ($user->is_active ? '停用后该成员将无法登录后台，已建立的会话将被即时强退。' : '启用后将恢复该成员的所有后台访问与操作权限。')"
                                        :confirmText="'确认' . ($user->is_active ? '停用' : '启用')"
                                        :confirmColor="$user->is_active ? 'amber' : 'emerald'"
                                        :icon="$user->is_active ? 'bi-pause-circle' : 'bi-play-circle'"
                                    />
                                @endcan

                                <!-- 删除成员二次确认 -->
                                @can('delete', $user)
                                    <x-admin::confirm-dialog
                                        id="hs-modal-confirm-delete-{{ $user->id }}"
                                        :action="route('admin.users.destroy', $user)"
                                        method="DELETE"
                                        title="确认永久删除该成员？"
                                        :message="'成员「<strong>' . e($user->name) . '</strong>」的账号数据将被彻底移除，此操作<strong>无法撤销</strong>。'"
                                        confirmText="确认永久删除"
                                        confirmColor="rose"
                                        icon="bi-exclamation-triangle"
                                    />
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

                <!-- 分页组件 (深度适配深浅色模式) -->
                <div class="p-3.5 px-4 border-t border-stone-200/60 dark:border-stone-800 flex items-center justify-between text-xs text-stone-400 font-mono text-[11px]">
                    <span>共 {{ $users->total() }} 名系统成员</span>
                    @if($users->hasPages())
                        <div>{{ $users->links('adminflow-core::pagination.nordic') }}</div>
                    @else
                        <span>状态正常 · 实时同步</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 右侧 4 列：常驻权限规则字典与安全控制规范 (仅在方案 B 双栏模式下呈现) -->
        @if($currentLayout === 'b')
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
        @endif
    </div>

    <!-- 添加成员 Modal -->
    @can('create', \AdminFlow\Core\Models\User::class)
        <x-admin::modal id="hs-modal-create-user" title="添加系统新成员" subtitle="为协作者分配访问账号与代码预设角色" icon="bi-person-plus">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="p-6 space-y-4 text-xs">
                    <div>
                        <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">成员姓名 <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="输入成员姓名" class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder:text-stone-400 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">登录邮箱 <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="user@example.com" class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder:text-stone-400 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">初始登录密码 <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="至少 8 位，包含英文字母与数字" class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 placeholder:text-stone-400 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">预设角色分配 <span class="text-rose-500">*</span></label>
                        <select name="role" required class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>
                                    {{ $role->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="create-is-active" name="is_active" value="1" checked class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                        <label for="create-is-active" class="text-xs text-stone-700 dark:text-stone-300">创建后立即允许登录后台</label>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-stone-50/60 dark:bg-stone-900/40 border-t border-stone-200/60 dark:border-stone-800/80 flex items-center justify-end gap-2.5">
                    <button type="button" class="px-3.5 py-2 rounded-lg text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-200/50 dark:hover:bg-stone-800 transition-colors" data-hs-overlay="#hs-modal-create-user">
                        取消
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
                        立即创建
                    </button>
                </div>
            </form>
        </x-admin::modal>
    @endcan
@endsection
