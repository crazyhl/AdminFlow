@props([
    'title',
    'description' => null,
])

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-xl font-serif-nordic font-bold text-stone-900 dark:text-stone-100 tracking-tight">
                {{ $title }}
            </h1>
            @if(isset($badge))
                {{ $badge }}
            @endif
        </div>
        @if($description)
            <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                {{ $description }}
            </p>
        @endif
    </div>

    @if(isset($actions) || $slot->isNotEmpty())
        <div class="flex items-center gap-3">
            {{ $actions ?? $slot }}
        </div>
    @endif
</div>
