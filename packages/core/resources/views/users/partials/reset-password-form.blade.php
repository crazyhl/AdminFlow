<form id="reset-password-form-{{ $user->id }}"
      method="POST"
      action="{{ route('admin.users.reset-password', $user) }}"
      hx-post="{{ route('admin.users.reset-password', $user) }}"
      hx-target="#reset-password-form-{{ $user->id }}"
      hx-swap="outerHTML"
      class="reset-password-form">
    @csrf
    <div class="p-6 space-y-4 text-xs">
        <!-- 新密码 -->
        <div>
            <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">
                新密码 <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <input type="password"
                       name="password"
                       required
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="至少 8 位，须包含字母与数字"
                       class="w-full px-3.5 py-2 pe-9 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border {{ $errors->has('password') ? 'border-rose-400 focus:border-rose-500' : 'border-stone-200 dark:border-stone-800' }} text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                <button type="button"
                        onclick="togglePasswordVisibility(this)"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                    <i class="bi bi-eye text-xs"></i>
                </button>
            </div>
            @if($errors->has('password'))
                <p class="text-rose-500 text-[11px] font-mono mt-1.5 flex items-center gap-1.5">
                    <i class="bi bi-x-circle text-xs"></i> {{ $errors->first('password') }}
                </p>
            @endif
        </div>

        <!-- 确认新密码 -->
        <div>
            <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">
                确认新密码 <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <input type="password"
                       name="password_confirmation"
                       required
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="再次输入新密码核对"
                       class="w-full px-3.5 py-2 pe-9 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border {{ $errors->has('password_confirmation') ? 'border-rose-400 focus:border-rose-500' : 'border-stone-200 dark:border-stone-800' }} text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                <button type="button"
                        onclick="togglePasswordVisibility(this)"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200">
                    <i class="bi bi-eye text-xs"></i>
                </button>
            </div>
            @if($errors->has('password_confirmation'))
                <p class="text-rose-500 text-[11px] font-mono mt-1.5 flex items-center gap-1.5">
                    <i class="bi bi-x-circle text-xs"></i> {{ $errors->first('password_confirmation') }}
                </p>
            @endif
            <div class="password-match-feedback text-[11px] font-mono mt-1 hidden"></div>
        </div>

        <!-- 安全提示 -->
        <div class="p-2.5 rounded-lg bg-stone-100/60 dark:bg-stone-800/40 border border-stone-200/60 dark:border-stone-700/50 flex items-center gap-2 text-[11px] text-stone-500 font-mono">
            <i class="bi bi-shield-check text-emerald-600"></i>
            <span>密码修改操作将在审计日志中记录，明文密码强制脱敏</span>
        </div>
    </div>

    <!-- 底部按钮区 -->
    <div class="px-6 py-3.5 bg-stone-50/60 dark:bg-stone-900/40 border-t border-stone-200/60 dark:border-stone-800/80 flex items-center justify-end gap-2.5">
        <button type="button"
                class="px-3.5 py-2 rounded-lg text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-200/50 dark:hover:bg-stone-800 transition-colors"
                data-hs-overlay="#hs-modal-reset-password-{{ $user->id }}">
            取消
        </button>
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
            <span>确认重置</span>
        </button>
    </div>
</form>
