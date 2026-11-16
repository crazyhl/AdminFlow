<!-- 全局 Toast 提示通知 (Nordic Minimal 风格) -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none">
    @if(session('success'))
        <div class="pointer-events-auto flex items-start gap-3 p-3.5 bg-white dark:bg-[#202020] rounded-xl border border-stone-200/80 dark:border-stone-800 shadow-lg text-xs transition-all duration-200" role="alert">
            <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <i class="bi bi-check2 text-xs"></i>
            </div>
            <div class="flex-1 text-stone-800 dark:text-stone-200 pt-0.5">
                {{ session('success') }}
            </div>
            <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                <i class="bi bi-x-lg text-[10px]"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="pointer-events-auto flex items-start gap-3 p-3.5 bg-white dark:bg-[#202020] rounded-xl border border-rose-200 dark:border-rose-900/60 shadow-lg text-xs transition-all duration-200" role="alert">
            <div class="w-5 h-5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <i class="bi bi-exclamation text-xs"></i>
            </div>
            <div class="flex-1 text-rose-700 dark:text-rose-300 pt-0.5">
                {{ session('error') }}
            </div>
            <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                <i class="bi bi-x-lg text-[10px]"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="pointer-events-auto flex items-start gap-3 p-3.5 bg-white dark:bg-[#202020] rounded-xl border border-rose-200 dark:border-rose-900/60 shadow-lg text-xs transition-all duration-200" role="alert">
            <div class="w-5 h-5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <i class="bi bi-exclamation-triangle text-xs"></i>
            </div>
            <div class="flex-1 text-stone-800 dark:text-stone-200 pt-0.5 space-y-1">
                <div class="font-medium text-rose-600 dark:text-rose-400">请核对以下输入错误：</div>
                <ul class="list-disc list-inside text-stone-600 dark:text-stone-400 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                <i class="bi bi-x-lg text-[10px]"></i>
            </button>
        </div>
    @endif
</div>

<script>
    // 自动消失 Toast
    document.addEventListener('DOMContentLoaded', () => {
        const alerts = document.querySelectorAll('#toast-container [role=alert]');
        alerts.forEach(el => {
            setTimeout(() => {
                el.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => el.remove(), 300);
            }, 4000);
        });
    });
</script>
