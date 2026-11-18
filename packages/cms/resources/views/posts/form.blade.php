@extends('adminflow-core::layouts.admin')

@section('title', $post->exists ? '编辑内容 · ' . $post->title : '新建内容 · Markdown 写作')
@section('breadcrumb', $post->exists ? '编辑内容' : '新建内容')

@push('styles')
    @vite(['packages/cms/resources/css/cms.css'])
@endpush

@section('content')
    <form id="post-form" 
          method="POST" 
          action="{{ $post->exists ? route('admin.cms.posts.update', $post) : route('admin.cms.posts.store') }}" 
          hx-post="{{ $post->exists ? route('admin.cms.posts.update', $post) : route('admin.cms.posts.store') }}"
          hx-target="#form-alert-container"
          hx-swap="innerHTML"
          hx-disabled-elt="#btn-save-post"
          class="w-full space-y-6">
        @csrf
        @if($post->exists)
            @method('PUT')
        @endif

        <!-- 乐观并发锁版本隐藏域 -->
        <input type="hidden" name="lock_version" value="{{ old('lock_version', $post->lock_version) }}">

        <!-- 顶栏操作区 -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-stone-200/70 dark:border-stone-800">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.cms.posts.index') }}" class="p-1.5 rounded-lg text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-200/50 dark:hover:bg-stone-800 transition-colors" title="返回内容列表">
                    <i class="bi bi-arrow-left text-base"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-serif-nordic font-bold text-stone-900 dark:text-stone-100 tracking-tight">
                            {{ $post->exists ? '编辑内容' : '新建文章 / 独立页面' }}
                        </h1>
                        <span id="post-version-badge" class="text-[11px] font-mono bg-stone-200/70 dark:bg-stone-800 text-stone-600 dark:text-stone-400 px-2 py-0.5 rounded transition-all" title="并发版本保护机制">
                            v{{ old('lock_version', $post->lock_version) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                @if($post->exists)
                    <!-- 查看受保护预览 -->
                    <a href="{{ route('admin.cms.posts.preview', $post) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-[#202020] text-stone-700 dark:text-stone-300 text-xs font-medium hover:bg-stone-50 dark:hover:bg-stone-800 transition-colors shadow-2xs">
                        <i class="bi bi-eye text-xs"></i>
                        <span>受保护预览</span>
                    </a>

                    @can('delete', $post)
                        <!-- 删除内容 (调起 Preline UI 二次确认弹窗) -->
                        <button type="button" data-hs-overlay="#hs-modal-confirm-delete-{{ $post->id }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-white dark:bg-[#202020] text-rose-600 dark:text-rose-400 text-xs font-medium hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors shadow-2xs" title="彻底删除内容">
                            <i class="bi bi-trash text-xs"></i>
                            <span>删除</span>
                        </button>
                    @endcan
                @endif

                <!-- 保存按钮 (支持 Cmd+S / Ctrl+S 快捷键) -->
                <button type="submit" id="btn-save-post" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg id="save-spinner" class="animate-spin -ml-0.5 h-3.5 w-3.5 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <i id="save-icon" class="bi bi-check2 text-xs"></i>
                    <span id="save-text">保存内容</span>
                    <kbd class="hidden sm:inline-block px-1.5 py-0.2 bg-stone-700 dark:bg-stone-300 text-stone-200 dark:text-stone-800 rounded text-[10px] font-mono">⌘S</kbd>
                </button>
            </div>
        </div>

        <!-- 异步挂载区：并发冲突 (409) 或校验异常 (422) 告警条 (HTMX 无刷新局部置换) -->
        <div id="form-alert-container" class="space-y-4 empty:hidden">
            @if(session('error'))
                @include('adminflow-cms::posts.partials.alert-conflict', ['message' => session('error')])
            @endif

            @if($errors->any())
                @include('adminflow-cms::posts.partials.alert-errors', ['errors' => $errors->all()])
            @endif
        </div>

        <!-- 文章元信息卡 (Notion 质感排印) -->
        <div class="p-6 rounded-2xl bg-white dark:bg-[#202020] border border-stone-200/70 dark:border-stone-800 space-y-5 shadow-2xs">
            <!-- 标题输入框 (微大字无框质感) -->
            <div>
                <input type="text" name="title" id="post-title" value="{{ old('title', $post->title) }}" placeholder="输入文章标题..." required class="w-full text-2xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 placeholder-stone-300 dark:placeholder-stone-600 border-0 border-b border-transparent hover:border-stone-200 dark:hover:border-stone-700 focus:border-stone-400 dark:focus:border-stone-500 focus:ring-0 bg-transparent px-0 py-1 transition-colors">
            </div>

            <!-- 固定链接 (Slug) 与前缀 -->
            <div class="flex items-center gap-1 text-xs font-mono text-stone-400 dark:text-stone-500">
                <span>https://site.local/posts/</span>
                <input type="text" name="slug" id="post-slug" value="{{ old('slug', $post->slug) }}" placeholder="自定义固定链接 (留空自动生成)" class="flex-1 max-w-sm px-2 py-1 rounded border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-[#191919] text-xs text-stone-800 dark:text-stone-200 focus:outline-none focus:ring-1 focus:ring-stone-400">
            </div>

            <!-- 属性折叠与控制胶囊栏 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-stone-100 dark:border-stone-800/80 text-xs">
                <div>
                    <label class="block font-medium text-stone-600 dark:text-stone-400 mb-1">内容类型</label>
                    <select name="type" data-hs-select='{
                        "placeholder": "选择内容类型...",
                        "toggleTag": "<button type=\"button\" aria-expanded=\"false\"></button>",
                        "toggleClasses": "hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-1.5 ps-3 pe-8 flex gap-x-2 text-nowrap w-full cursor-pointer bg-stone-50/60 dark:bg-[#191919] border border-stone-200 dark:border-stone-700 rounded-lg text-start text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:ring-1 focus:ring-stone-400 transition-colors",
                        "dropdownClasses": "mt-1.5 z-50 w-full p-1 space-y-0.5 bg-white dark:bg-[#191919] border border-stone-200 dark:border-stone-700 rounded-lg shadow-lg overflow-hidden overflow-y-auto max-h-60",
                        "optionClasses": "py-1.5 px-2.5 w-full text-xs text-stone-800 dark:text-stone-200 cursor-pointer hover:bg-stone-100 dark:hover:bg-stone-800 rounded-md focus:outline-hidden focus:bg-stone-100 dark:focus:bg-stone-800",
                        "optionTemplate": "<div class=\"flex justify-between items-center w-full\"><span data-title></span><span class=\"hidden hs-selected:block\"><svg class=\"shrink-0 size-3 text-stone-900 dark:text-stone-100\" xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2.5\"><polyline points=\"20 6 9 17 4 12\"/></svg></span></div>",
                        "extraMarkup": "<div class=\"absolute top-1/2 end-2.5 -translate-y-1/2 pointer-events-none\"><svg class=\"shrink-0 size-3 text-stone-500 dark:text-stone-400\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m6 9 6 6 6-6\"/></svg></div>"
                    }' class="hidden">
                        @foreach($types as $type)
                            <option value="{{ $type->value }}" {{ old('type', $post->type?->value ?? 'post') === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-stone-600 dark:text-stone-400 mb-1">发布状态</label>
                    <select name="status" data-hs-select='{
                        "placeholder": "选择发布状态...",
                        "toggleTag": "<button type=\"button\" aria-expanded=\"false\"></button>",
                        "toggleClasses": "hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-1.5 ps-3 pe-8 flex gap-x-2 text-nowrap w-full cursor-pointer bg-stone-50/60 dark:bg-[#191919] border border-stone-200 dark:border-stone-700 rounded-lg text-start text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:ring-1 focus:ring-stone-400 transition-colors",
                        "dropdownClasses": "mt-1.5 z-50 w-full p-1 space-y-0.5 bg-white dark:bg-[#191919] border border-stone-200 dark:border-stone-700 rounded-lg shadow-lg overflow-hidden overflow-y-auto max-h-60",
                        "optionClasses": "py-1.5 px-2.5 w-full text-xs text-stone-800 dark:text-stone-200 cursor-pointer hover:bg-stone-100 dark:hover:bg-stone-800 rounded-md focus:outline-hidden focus:bg-stone-100 dark:focus:bg-stone-800",
                        "optionTemplate": "<div class=\"flex justify-between items-center w-full\"><span data-title></span><span class=\"hidden hs-selected:block\"><svg class=\"shrink-0 size-3 text-stone-900 dark:text-stone-100\" xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2.5\"><polyline points=\"20 6 9 17 4 12\"/></svg></span></div>",
                        "extraMarkup": "<div class=\"absolute top-1/2 end-2.5 -translate-y-1/2 pointer-events-none\"><svg class=\"shrink-0 size-3 text-stone-500 dark:text-stone-400\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m6 9 6 6 6-6\"/></svg></div>"
                    }' class="hidden">
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ old('status', $post->status?->value ?? 'draft') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-stone-600 dark:text-stone-400 mb-1">发布时间 (可选)</label>
                    <div class="relative">
                        <input type="text"
                               id="published_at_picker"
                               name="published_at"
                               data-flatpickr
                               value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i')) }}"
                               placeholder="发布时间 (默认当前时间)"
                               autocomplete="off"
                               class="w-full ps-2.5 pe-8 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50/60 dark:bg-[#191919] text-xs text-stone-800 dark:text-stone-200 placeholder:text-stone-400 dark:placeholder:text-stone-500 focus:outline-hidden focus:ring-1 focus:ring-stone-400 transition-colors cursor-pointer">
                        <div class="absolute top-1/2 end-2.5 -translate-y-1/2 pointer-events-none text-stone-400 dark:text-stone-500">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-5">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="hidden" name="is_pinned" value="0">
                        <span class="relative flex items-center justify-center">
                            <input type="checkbox" id="is_pinned" name="is_pinned" value="1" {{ old('is_pinned', $post->is_pinned) ? 'checked' : '' }} class="peer appearance-none w-4 h-4 rounded-sm border border-stone-300 dark:border-stone-600 bg-white dark:bg-[#191919] checked:bg-stone-900 dark:checked:bg-stone-100 checked:border-stone-900 dark:checked:border-stone-100 focus:outline-none focus:ring-1 focus:ring-stone-400 transition-colors cursor-pointer">
                            <svg class="absolute w-2.5 h-2.5 text-white dark:text-stone-900 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-xs font-medium text-stone-700 dark:text-stone-300">置顶显示</span>
                    </label>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="hidden" name="allow_comments" value="0">
                        <span class="relative flex items-center justify-center">
                            <input type="checkbox" id="allow_comments" name="allow_comments" value="1" {{ old('allow_comments', $post->allow_comments) ? 'checked' : '' }} class="peer appearance-none w-4 h-4 rounded-sm border border-stone-300 dark:border-stone-600 bg-white dark:bg-[#191919] checked:bg-stone-900 dark:checked:bg-stone-100 checked:border-stone-900 dark:checked:border-stone-100 focus:outline-none focus:ring-1 focus:ring-stone-400 transition-colors cursor-pointer">
                            <svg class="absolute w-2.5 h-2.5 text-white dark:text-stone-900 pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-xs font-medium text-stone-700 dark:text-stone-300">开启评论</span>
                    </label>
                </div>
            </div>

            <!-- 文章摘要 (可选) -->
            <div>
                <label class="block font-medium text-stone-600 dark:text-stone-400 mb-1 text-xs">文章摘要 (可选，留空将从正文自动提取)</label>
                <textarea name="summary" rows="2" placeholder="写一句简短的文章摘要..." class="w-full px-3 py-2 rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50/60 dark:bg-[#191919] text-xs text-stone-800 dark:text-stone-200 placeholder-stone-400 focus:ring-1 focus:ring-stone-400">{{ old('summary', $post->summary) }}</textarea>
            </div>
        </div>

        <!-- Nordic 原生轻量双栏 Markdown 编辑器 -->
        <div id="editor-wrapper"
             data-post-editor
             data-preview-url="{{ route('admin.cms.posts.preview-render') }}"
             class="rounded-2xl bg-white dark:bg-[#202020] border border-stone-200/70 dark:border-stone-800 flex flex-col shadow-2xs overflow-hidden transition-all duration-200">
            <!-- 编辑器工具栏 -->
            <div class="h-11 px-4 border-b border-stone-200/70 dark:border-stone-800 flex items-center justify-between bg-stone-50/70 dark:bg-[#222222] select-none text-xs">
                <!-- 快捷格式化按钮组 -->
                <div class="flex items-center gap-1 text-stone-600 dark:text-stone-400">
                    <button type="button" data-action="insert" data-prefix="**" data-suffix="**" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="加粗 (Ctrl+B)">
                        <i class="bi bi-type-bold"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="*" data-suffix="*" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="斜体 (Ctrl+I)">
                        <i class="bi bi-type-italic"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="## " data-suffix="" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="二级标题">
                        <i class="bi bi-type-h2"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="### " data-suffix="" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="三级标题">
                        <i class="bi bi-type-h3"></i>
                    </button>
                    <div class="h-4 w-px bg-stone-200 dark:bg-stone-700 mx-1"></div>
                    <button type="button" data-action="insert" data-prefix="> " data-suffix="" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="引用块">
                        <i class="bi bi-quote"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="`" data-suffix="`" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="行内代码">
                        <i class="bi bi-code"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="```&#10;" data-suffix="&#10;```" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="围栏代码块">
                        <i class="bi bi-code-square"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="[链接描述](" data-suffix=")" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="超链接">
                        <i class="bi bi-link-45deg"></i>
                    </button>
                    <button type="button" data-action="insert" data-prefix="![图片描述](" data-suffix=")" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="插入图片">
                        <i class="bi bi-image"></i>
                    </button>
                    <button type="button" data-action="table" class="p-1.5 rounded hover:bg-stone-200/60 dark:hover:bg-stone-700" title="插入 Markdown 表格">
                        <i class="bi bi-table"></i>
                    </button>
                </div>

                <!-- 模式切换与字数统计 -->
                <div class="flex items-center gap-3">
                    <span id="word-count" class="text-[11px] font-mono text-stone-400">0 字</span>

                    <!-- 模式切换胶囊 -->
                    <div class="inline-flex items-center p-0.5 rounded-lg bg-stone-200/60 dark:bg-stone-800 text-[11px]">
                        <button type="button" data-action="mode" data-mode="split" id="btn-mode-split" class="px-2 py-0.5 rounded-md font-medium bg-white dark:bg-[#191919] text-stone-900 dark:text-stone-100 shadow-2xs" title="双栏对照">
                            <i class="bi bi-layout-split"></i>
                        </button>
                        <button type="button" data-action="mode" data-mode="edit" id="btn-mode-edit" class="px-2 py-0.5 rounded-md font-medium text-stone-500 hover:text-stone-900 dark:hover:text-stone-100" title="纯写作">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" data-action="mode" data-mode="preview" id="btn-mode-preview" class="px-2 py-0.5 rounded-md font-medium text-stone-500 hover:text-stone-900 dark:hover:text-stone-100" title="纯排版预览">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <!-- 全屏沉浸写作按钮 -->
                    <button type="button" data-action="fullscreen" class="p-1.5 rounded text-stone-400 hover:text-stone-700 dark:hover:text-stone-200" title="全屏沉浸模式 (F11)">
                        <i class="bi bi-arrows-fullscreen text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- 主编辑分栏工作区 -->
            <div id="editor-grid" class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-stone-200/70 dark:divide-stone-800 flex-1 min-h-[580px]">
                <!-- 左栏：Markdown 源码输入区 -->
                <div id="editor-pane-left" class="relative flex flex-col p-4 bg-transparent">
                    <textarea name="body_markdown" id="markdown-textarea" rows="25" placeholder="在此输入 Markdown 原文... 支持拖拽或粘贴图片上传" required class="w-full flex-1 p-2 font-mono text-xs leading-relaxed text-stone-800 dark:text-stone-200 bg-transparent border-0 focus:ring-0 focus:outline-none resize-none select-text">{{ old('body_markdown', $post->body_markdown) }}</textarea>

                    <!-- 拖拽提示遮罩 -->
                    <div id="drop-overlay" class="absolute inset-0 bg-stone-100/90 dark:bg-stone-900/90 border-2 border-dashed border-stone-400 dark:border-stone-600 rounded-xl flex flex-col items-center justify-center pointer-events-none hidden z-20">
                        <i class="bi bi-cloud-arrow-up text-3xl text-stone-500 mb-2"></i>
                        <span class="text-xs font-medium text-stone-700 dark:text-stone-300">松开鼠标自动解析并插入图片</span>
                    </div>
                </div>

                <!-- 右栏：实时排版渲染区 -->
                <div id="editor-pane-right" class="relative flex flex-col p-6 overflow-y-auto bg-stone-50/30 dark:bg-[#1c1c1c]/40">
                    <div id="markdown-preview" class="prose prose-stone dark:prose-invert max-w-none w-full text-stone-800 dark:text-stone-200">
                        <!-- 实时渲染注入点 -->
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if($post->exists)
        @can('delete', $post)
            <x-admin::delete-dialog
                id="hs-modal-confirm-delete-{{ $post->id }}"
                :action="route('admin.cms.posts.destroy', ['post' => $post, 'lock_version' => $post->lock_version])"
                title="确认彻底删除该内容？"
                :item="$post->title"
                confirmText="确认彻底删除"
            >
                <input type="hidden" name="lock_version" value="{{ $post->lock_version }}">
            </x-admin::delete-dialog>
        @endcan
    @endif
@endsection

@push('scripts')
    @vite(['packages/cms/resources/js/cms-editor.js'])
@endpush
