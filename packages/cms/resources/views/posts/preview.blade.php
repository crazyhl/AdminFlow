@extends('adminflow-core::layouts.admin')

@section('title', '受保护预览 · ' . $post->title)
@section('breadcrumb', '受保护内容预览')

@push('styles')
    @vite(['packages/cms/resources/css/cms.css'])
@endpush

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- 权限隔离安全提示横幅 -->
        <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="bi bi-shield-lock-fill text-amber-600 text-sm"></i>
                <span class="font-medium">受保护预览模式：</span>
                <span class="text-stone-600 dark:text-stone-400">当前内容受鉴权策略保护，未经公开前绝不会泄露给外部访客、RSS 或 Sitemap。</span>
            </div>
            @can('update', $post)
                <a href="{{ route('admin.cms.posts.edit', $post) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-100 text-[11px] font-semibold hover:bg-amber-300 transition-colors">
                    <i class="bi bi-pencil-square"></i>
                    <span>返回编辑</span>
                </a>
            @else
                <a href="{{ route('admin.cms.posts.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-100 text-[11px] font-semibold hover:bg-amber-300 transition-colors">
                    <i class="bi bi-arrow-left"></i>
                    <span>返回列表</span>
                </a>
            @endcan
        </div>

        <!-- 渲染正文卡片 (Nordic Minimal 沉浸阅读排版) -->
        <article class="p-8 sm:p-12 rounded-3xl bg-white dark:bg-[#202020] border border-stone-200/70 dark:border-stone-800 shadow-xs space-y-8">
            <!-- 头部元信息 -->
            <header class="space-y-4 pb-6 border-b border-stone-100 dark:border-stone-800">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono {{ $post->type === \AdminFlow\CMS\Enums\PostType::PAGE ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300' }}">
                        {{ $post->type->label() }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-medium {{ $post->status->badgeColor() }}">
                        {{ $post->status->label() }}
                    </span>
                    @if($post->is_pinned)
                        <span class="px-2 py-0.5 rounded text-[11px] bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                            置顶
                        </span>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-4xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 tracking-tight leading-tight">
                    {{ $post->title }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-xs text-stone-400 font-mono">
                    <span class="flex items-center gap-1.5">
                        <i class="bi bi-person"></i>
                        <span>{{ $post->author?->name ?? '系统管理员' }}</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i class="bi bi-clock"></i>
                        <span>{{ $post->published_at ? $post->published_at->format('Y-m-d H:i') : '未正式发布 (草稿)' }}</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i class="bi bi-shield-check"></i>
                        <span>并发锁 v{{ $post->lock_version }}</span>
                    </span>
                </div>

                @if($post->summary)
                    <p class="text-sm text-stone-500 dark:text-stone-400 italic bg-stone-50 dark:bg-[#191919] p-4 rounded-xl border border-stone-200/50 dark:border-stone-800/60">
                        {{ $post->summary }}
                    </p>
                @endif
            </header>

            <!-- 封面图 (若有) -->
            @if($post->cover_image_url)
                <div class="rounded-2xl overflow-hidden border border-stone-200/60 dark:border-stone-800 shadow-2xs">
                    <img src="{{ $post->cover_image_url }}" alt="{{ $post->title }}" class="w-full h-auto object-cover max-h-[400px]">
                </div>
            @endif

            <!-- 正文渲染区 (安全 HTML 呈现) -->
            <div class="prose prose-stone dark:prose-invert max-w-none text-stone-800 dark:text-stone-200 text-sm leading-relaxed">
                {!! $post->body_html !!}
            </div>
        </article>
    </div>
@endsection

@push('scripts')
    @vite(['packages/cms/resources/js/cms-preview.js'])
@endpush
