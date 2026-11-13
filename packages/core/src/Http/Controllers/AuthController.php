<?php

namespace AdminFlow\Core\Http\Controllers;

use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthController extends Controller
{
    /**
     * 显示管理员登录表单
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('adminflow-core::auth.login');
    }

    /**
     * 处理登录提交
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditLogger::record(
                action: 'auth.login_locked',
                details: [
                    'email' => $credentials['email'],
                    'available_in_seconds' => $seconds,
                ],
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        // 尝试认证
        if (!Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            AuditLogger::record(
                action: 'auth.login_failed',
                details: [
                    'email' => $credentials['email'],
                    'reason' => 'invalid_credentials',
                ],
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );

            throw ValidationException::withMessages([
                'email' => '邮箱或密码不正确',
            ]);
        }

        $user = Auth::guard('admin')->user();

        // 检查账号是否被停用
        if (!$user instanceof User || !$user->isActive()) {
            Auth::guard('admin')->logout();

            if ($user instanceof User) {
                AuditLogger::record(
                    action: 'auth.login_rejected',
                    target: $user,
                    details: [
                        'email' => $user->email,
                        'reason' => 'account_inactive',
                    ],
                    userId: $user->id,
                    ip: $request->ip(),
                    userAgent: $request->userAgent(),
                );
            }

            throw ValidationException::withMessages([
                'email' => '该账号已被停用，禁止登录',
            ]);
        }

        // 认证成功：清除限流计数并重新生成 Session ID 防固化攻击
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        AuditLogger::record(
            action: 'auth.login_success',
            target: $user,
            details: [
                'email' => $user->email,
            ],
            userId: $user->id,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * 处理登出
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if ($user instanceof User) {
            AuditLogger::record(
                action: 'auth.logout',
                target: $user,
                details: [
                    'email' => $user->email,
                ],
                userId: $user->id,
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        }

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
