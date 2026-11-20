@extends('adminflow-core::layouts.admin')

@section('title', '内容写作与管理')
@section('breadcrumb', '内容写作与管理')

@section('content')
    <div class="w-full space-y-6">
        <!-- 页面标题与操作区 -->
        <x-admin::page-header title="内容写作与管理" description="管理站点 Markdown 文章与独立页面，支持受保护预览、草稿流转与并发版本控制。">
            <x-slot:actions>
                @can('create', \AdminFlow\CMS\Models\Post::class)
                    <a href="{{ route('admin.cms.posts.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
                        <i class="bi bi-pencil-square text-xs"></i>
                        <span>撰写新文章 / 页面</span>
                    </a>
                @endcan
            </x-slot:actions>
        </x-admin::page-header>

        <!-- 极简微统计卡 (Nordic Minimal 排印) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 py-4 border-y border-stone-200/60 dark:border-stone-800">
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">内容总数</div>
                <div class="text-2xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-0.5">
                    {{ $statistics['total'] }}
                </div>
            </div>
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">已发布</div>
                <div class="text-2xl font-serif-nordic font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">
                    {{ $statistics['published'] }}
                </div>
            </div>
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">草稿待发</div>
                <div class="text-2xl font-serif-nordic font-bold text-amber-700 dark:text-amber-400 mt-0.5">
                    {{ $statistics['draft'] }}
                </div>
            </div>
            <div>
                <div class="text-xs text-stone-400 dark:text-stone-500">独立单页</div>
                <div class="text-2xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 mt-0.5">
                    {{ $statistics['pages'] }}
                </div>
            </div>
        </div>

        <!-- 搜索与筛选表单 (HTMX 无刷新即时联动) -->
        <form method="GET"
              action="{{ route('admin.cms.posts.index') }}"
              hx-get="{{ route('admin.cms.posts.index') }}"
              hx-trigger="change from:select, input changed delay:300ms from:input[name='search'], submit"
              hx-target="#posts-table-card"
              hx-swap="outerHTML"
              hx-push-url="true"
              class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 text-xs"></i>
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="按标题或固定链接即时搜索..."
                       autocomplete="off"
                       class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-[#202020] text-xs text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:outline-none focus:ring-1 focus:ring-stone-400 dark:focus:ring-stone-600 transition-colors">
            </div>

            <div class="w-32">
                <select name="type" class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-[#202020] text-xs text-stone-700 dark:text-stone-300 focus:outline-none focus:ring-1 focus:ring-stone-400">
                    <option value="">全部类型</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ ($filters['type'] ?? '') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-32">
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-[#202020] text-xs text-stone-700 dark:text-stone-300 focus:outline-none focus:ring-1 focus:ring-stone-400">
                    <option value="">全部状态</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-3 py-1.5 rounded-lg bg-stone-200/70 dark:bg-stone-800 text-stone-700 dark:text-stone-300 text-xs font-medium hover:bg-stone-200 dark:hover:bg-stone-700 transition-colors">
                筛选
            </button>

            @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['type']))
                <a href="{{ route('admin.cms.posts.index') }}"
                   hx-get="{{ route('admin.cms.posts.index') }}"
                   hx-target="#posts-table-card"
                   hx-swap="outerHTML"
                   hx-push-url="true"
                   hx-on::after-request="if (event.detail.successful) this.remove()"
                   onclick="const f = this.closest('form'); if (f) { f.reset(); f.querySelectorAll('input[name=search]').forEach(el => el.value = ''); f.querySelectorAll('select').forEach(el => el.value = ''); } this.addEventListener('htmx:afterRequest', (e) => { if (e.detail.successful) this.remove(); }, { once: true });"
                   class="text-xs text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 transition-colors">
                    重置
                </a>
            @endif
        </form>

        <!-- 内容数据表格组件容器 (HTMX 置换目标) -->
        @include('adminflow-cms::posts.partials.table')
    </div>
@endsection
