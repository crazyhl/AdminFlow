<?php

namespace AdminFlow\Core\Http\Middleware;

use AdminFlow\Core\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    /**
     * 处理进入后台受保护路由的请求
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('admin.login'));
        }

        $user = Auth::guard('admin')->user();

        // 会话有效性与账号状态二次核验：若账号在会话期间被管理员停用，立即强退
        if (!$user instanceof User || !$user->isActive()) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => '账号已被停用'], 403);
            }

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'email' => '您的账号已被停用，已强制退出系统',
                ]);
        }

        return $next($request);
    }
}
