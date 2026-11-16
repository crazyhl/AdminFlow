@props([
    'label',
    'value',
    'subtext' => null,
    'color' => 'stone', // stone, emerald, amber, rose, violet
])

@php
    $colorMap = [
        'stone' => 'text-stone-900 dark:text-stone-100',
        'emerald' => 'text-emerald-700 dark:text-emerald-400',
        'amber' => 'text-amber-700 dark:text-amber-400',
        'rose' => 'text-rose-700 dark:text-rose-400',
        'violet' => 'text-violet-700 dark:text-violet-400',
    ];
    $valClass = $colorMap[$color] ?? $colorMap['stone'];
@endphp

<div class="bg-white dark:bg-[#202020] p-4 rounded-xl border border-stone-200/70 dark:border-stone-800 shadow-[0_1px_3px_rgba(0,0,0,0.02)] transition-colors duration-150">
    <div class="text-xs text-stone-400 dark:text-stone-500 font-medium">{{ $label }}</div>
    <div class="text-3xl font-serif-nordic font-bold {{ $valClass }} mt-1">
        {{ $value }}
    </div>
    @if($subtext)
        <div class="text-[11px] text-stone-400 dark:text-stone-500 mt-1 font-mono truncate">
            {{ $subtext }}
        </div>
    @endif
</div>
