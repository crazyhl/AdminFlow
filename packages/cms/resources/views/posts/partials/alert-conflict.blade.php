<div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs space-y-2 animate-in fade-in slide-in-from-top-1 duration-200">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 font-medium">
            <i class="bi bi-exclamation-triangle-fill text-amber-600 text-sm"></i>
            <span>{{ $message ?? '内容已被他人修改（当前最新版本: v' . ($currentVersion ?? '最新') . '，您提交的版本: v' . ($expectedVersion ?? '旧版') . '），保存已被拦截，请刷新以防覆盖。' }}</span>
        </div>
        <button type="button" onclick="window.location.reload()" class="px-2.5 py-1 rounded-md bg-amber-200/80 dark:bg-amber-900 text-amber-900 dark:text-amber-100 text-[11px] font-semibold hover:bg-amber-300 dark:hover:bg-amber-800 transition-colors cursor-pointer">
            重新加载最新数据
        </button>
    </div>
    <p class="text-stone-600 dark:text-stone-400 text-[11px]">
        为防数据被无意覆盖，系统已自动阻断本次写入。您可以核对并复制下方修改，重新同步后再行保存。
    </p>
</div>
