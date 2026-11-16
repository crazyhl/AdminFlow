@if ($paginator->hasPages())
    <nav role="navigation" aria-label="分页导航" class="flex items-center gap-1.5 text-xs font-mono">
        {{-- 上一页按钮 --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-stone-200/50 dark:border-stone-800 text-stone-300 dark:text-stone-700 cursor-not-allowed" aria-disabled="true" aria-label="上一页">
                <i class="bi bi-chevron-left text-xs"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" aria-label="上一页">
                <i class="bi bi-chevron-left text-xs"></i>
            </a>
        @endif

        {{-- 页码序列 --}}
        @foreach ($elements as $element)
            {{-- 三点省略号 "..." --}}
            @if (is_string($element))
                <span class="inline-flex items-center justify-center w-8 h-8 text-stone-400 dark:text-stone-600 cursor-default">
                    {{ $element }}
                </span>
            @endif

            {{-- 具体的页码链接数组 --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 font-bold shadow-xs">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-transparent hover:border-stone-200 dark:hover:border-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- 下一页按钮 --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 hover:text-stone-900 dark:hover:text-stone-100 transition-colors" aria-label="下一页">
                <i class="bi bi-chevron-right text-xs"></i>
            </a>
        @else
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-stone-200/50 dark:border-stone-800 text-stone-300 dark:text-stone-700 cursor-not-allowed" aria-disabled="true" aria-label="下一页">
                <i class="bi bi-chevron-right text-xs"></i>
            </span>
        @endif
    </nav>
@endif
