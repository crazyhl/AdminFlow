@props([
    'variant' => 'neutral', // neutral, success, warning, danger, info
])

@php
    $variantClasses = [
        'neutral' => 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400 border-stone-200/80 dark:border-stone-700/60',
        'success' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border-emerald-200/80 dark:border-emerald-900/60',
        'warning' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-200/80 dark:border-amber-900/60',
        'danger' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-200/80 dark:border-rose-900/60',
        'info' => 'bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400 border-sky-200/80 dark:border-sky-900/60',
    ];
    $cls = $variantClasses[$variant] ?? $variantClasses['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-mono border $cls"]) }}>
    {{ $slot }}
</span>
