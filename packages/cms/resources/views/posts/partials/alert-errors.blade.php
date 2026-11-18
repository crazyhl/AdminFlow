<div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-xs animate-in fade-in slide-in-from-top-1 duration-200">
    <div class="font-medium flex items-center gap-1.5 mb-1">
        <i class="bi bi-x-circle-fill text-rose-600"></i>
        <span>表单校验未通过，请检查以下项：</span>
    </div>
    <ul class="list-disc list-inside space-y-0.5 text-stone-600 dark:text-stone-400">
        @foreach($errors as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
