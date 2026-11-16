@props([
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-[#202020] rounded-xl border border-stone-200/70 dark:border-stone-800 shadow-[0_1px_3px_rgba(0,0,0,0.02)] overflow-hidden transition-colors duration-150']) }}>
    <div class="overflow-x-auto">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="border-t border-stone-200/60 dark:border-stone-800 bg-stone-50/40 dark:bg-stone-900/20">
            {{ $footer }}
        </div>
    @endif
</div>
