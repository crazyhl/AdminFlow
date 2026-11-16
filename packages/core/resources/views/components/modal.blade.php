@props([
    'id',
    'title',
    'subtitle' => null,
    'icon' => null,
    'maxWidth' => 'max-w-md',
])

<div id="{{ $id }}" class="hs-overlay hidden fixed inset-0 z-70 overflow-x-hidden overflow-y-auto bg-stone-900/50 backdrop-blur-xs flex items-center justify-center p-4" tabindex="-1">
    <div class="hs-overlay-open:opacity-100 hs-overlay-open:scale-100 opacity-0 scale-95 transition-all duration-200 w-full {{ $maxWidth }} bg-white dark:bg-[#202020] rounded-2xl border border-stone-200/80 dark:border-stone-800 shadow-2xl overflow-hidden text-left">
        <!-- 头部 -->
        <div class="px-6 py-5 border-b border-stone-200/60 dark:border-stone-800/80 flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                @if($icon)
                    <div class="w-10 h-10 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 flex items-center justify-center shrink-0 border border-stone-200/60 dark:border-stone-700">
                        <i class="bi {{ $icon }} text-base"></i>
                    </div>
                @endif
                <div>
                    <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100 tracking-tight">
                        {{ $title }}
                    </h3>
                    @if($subtitle)
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            </div>
            <button type="button" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 p-1" data-hs-overlay="#{{ $id }}" aria-label="关闭">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <!-- 内容体 -->
        {{ $slot }}

        <!-- 底部 (可选) -->
        @if(isset($footer))
            <div class="px-6 py-3.5 bg-stone-50/60 dark:bg-stone-900/40 border-t border-stone-200/60 dark:border-stone-800/80 flex items-center justify-end gap-2.5">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
