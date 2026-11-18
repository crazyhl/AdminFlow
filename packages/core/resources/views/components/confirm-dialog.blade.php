@props([
    'id',
    'action',
    'method' => 'POST',
    'title',
    'message',
    'confirmText' => '确认提交',
    'confirmColor' => 'rose', // rose, amber, emerald, stone
    'icon' => 'bi-exclamation-triangle',
])

@php
    $colorMap = [
        'rose' => [
            'icon_bg' => 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400',
            'btn_bg' => 'bg-rose-600 hover:bg-rose-700 text-white',
        ],
        'amber' => [
            'icon_bg' => 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400',
            'btn_bg' => 'bg-amber-600 hover:bg-amber-700 text-white',
        ],
        'emerald' => [
            'icon_bg' => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400',
            'btn_bg' => 'bg-emerald-600 hover:bg-emerald-700 text-white',
        ],
        'stone' => [
            'icon_bg' => 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300',
            'btn_bg' => 'bg-stone-900 hover:bg-stone-800 dark:bg-stone-100 dark:hover:bg-stone-200 text-white dark:text-stone-900',
        ],
    ];
    $theme = $colorMap[$confirmColor] ?? $colorMap['rose'];
@endphp

<div id="{{ $id }}" class="hs-overlay hidden fixed inset-0 z-70 overflow-x-hidden overflow-y-auto bg-stone-900/50 backdrop-blur-xs flex items-center justify-center p-4" tabindex="-1">
    <div class="hs-overlay-open:opacity-100 hs-overlay-open:scale-100 opacity-0 scale-95 transition-all duration-200 w-full max-w-sm bg-white dark:bg-[#202020] rounded-2xl border border-stone-200/80 dark:border-stone-800 shadow-2xl p-6 text-center space-y-4">
        <!-- 警示图标 -->
        <div class="w-12 h-12 rounded-full {{ $theme['icon_bg'] }} flex items-center justify-center mx-auto text-xl">
            <i class="bi {{ $icon }}"></i>
        </div>

        <!-- 标题与释义 -->
        <div class="space-y-1">
            <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">
                {{ $title }}
            </h3>
            <p class="text-xs text-stone-500 dark:text-stone-400 leading-relaxed">
                {!! $message !!}
            </p>
        </div>

        <!-- 动作操作表单 -->
        <form method="POST" action="{{ $action }}" class="flex items-center justify-center gap-2.5 pt-1">
            @csrf
            @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
                @method($method)
            @endif
            {{ $slot ?? '' }}

            <button type="button" class="w-full px-4 py-2 rounded-lg border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-400 text-xs font-medium hover:bg-stone-100 dark:hover:bg-stone-800 transition-colors" data-hs-overlay="#{{ $id }}">
                取消
            </button>
            <button type="submit" class="w-full px-4 py-2 rounded-lg {{ $theme['btn_bg'] }} text-xs font-medium transition-colors shadow-xs">
                {{ $confirmText }}
            </button>
        </form>
    </div>
</div>
