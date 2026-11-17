<form id="create-user-form"
      method="POST"
      action="{{ route('admin.users.store') }}"
      hx-post="{{ route('admin.users.store') }}"
      hx-target="#create-user-form"
      hx-swap="outerHTML">
    @csrf
    <div class="p-6 space-y-4 text-xs">
        <!-- 成员姓名 -->
        <div>
            <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">
                成员姓名 <span class="text-rose-500">*</span>
            </label>
            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   placeholder="输入成员姓名"
                   class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border {{ $errors->has('name') ? 'border-rose-400 focus:border-rose-500' : 'border-stone-200 dark:border-stone-800' }} text-xs text-stone-900 dark:text-stone-100 placeholder:text-stone-400 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
            @if($errors->has('name'))
                <p class="text-rose-500 text-[11px] font-mono mt-1.5 flex items-center gap-1.5">
                    <i class="bi bi-x-circle text-xs"></i> {{ $errors->first('name') }}
                </p>
            @endif
        </div>

        <!-- 登录邮箱 -->
        <div>
            <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">
                登录邮箱 <span class="text-rose-500">*</span>
            </label>
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   placeholder="user@example.com"
                   class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border {{ $errors->has('email') ? 'border-rose-400 focus:border-rose-500' : 'border-stone-200 dark:border-stone-800' }} text-xs text-stone-900 dark:text-stone-100 placeholder:text-stone-400 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
            @if($errors->has('email'))
                <p class="text-rose-500 text-[11px] font-mono mt-1.5 flex items-center gap-1.5">
                    <i class="bi bi-x-circle text-xs"></i> {{ $errors->first('email') }}
                </p>
            @endif
        </div>

        <!-- 初始密码 -->
        <div>
            <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">
                初始登录密码 <span class="text-rose-500">*</span>
            </label>
            <input type="password"
                   name="password"
                   required
                   minlength="8"
                   placeholder="至少 8 位，包含英文字母与数字"
                   class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border {{ $errors->has('password') ? 'border-rose-400 focus:border-rose-500' : 'border-stone-200 dark:border-stone-800' }} text-xs text-stone-900 dark:text-stone-100 placeholder:text-stone-400 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
            @if($errors->has('password'))
                <p class="text-rose-500 text-[11px] font-mono mt-1.5 flex items-center gap-1.5">
                    <i class="bi bi-x-circle text-xs"></i> {{ $errors->first('password') }}
                </p>
            @endif
        </div>

        <!-- 预设角色分配 -->
        <div>
            <label class="block text-xs font-medium text-stone-700 dark:text-stone-300 mb-1.5">
                预设角色分配 <span class="text-rose-500">*</span>
            </label>
            <select name="role"
                    required
                    class="w-full px-3.5 py-2 rounded-lg bg-stone-50/70 dark:bg-stone-900/60 border {{ $errors->has('role') ? 'border-rose-400' : 'border-stone-200 dark:border-stone-800' }} text-xs text-stone-900 dark:text-stone-100 focus:bg-white dark:focus:bg-[#1a1a1a] focus:outline-hidden focus:border-stone-400 transition-colors">
                @foreach($roles as $role)
                    <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>
                        {{ $role->label() }}
                    </option>
                @endforeach
            </select>
            @if($errors->has('role'))
                <p class="text-rose-500 text-[11px] font-mono mt-1.5 flex items-center gap-1.5">
                    <i class="bi bi-x-circle text-xs"></i> {{ $errors->first('role') }}
                </p>
            @endif
        </div>

        <!-- 立即允许登录开关 -->
        <div class="flex items-center gap-2 pt-1">
            <input type="checkbox"
                   id="create-is-active"
                   name="is_active"
                   value="1"
                   {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                   class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
            <label for="create-is-active" class="text-xs text-stone-700 dark:text-stone-300">
                创建后立即允许登录后台
            </label>
        </div>
    </div>

    <!-- 底部按钮区 -->
    <div class="px-6 py-3.5 bg-stone-50/60 dark:bg-stone-900/40 border-t border-stone-200/60 dark:border-stone-800/80 flex items-center justify-end gap-2.5">
        <button type="button"
                class="px-3.5 py-2 rounded-lg text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-200/50 dark:hover:bg-stone-800 transition-colors"
                data-hs-overlay="#hs-modal-create-user">
            取消
        </button>
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-stone-900 dark:bg-stone-100 text-stone-100 dark:text-stone-900 text-xs font-medium hover:bg-stone-800 dark:hover:bg-stone-200 transition-colors shadow-xs">
            <span>立即创建</span>
        </button>
    </div>
</form>
