<?php

namespace AdminFlow\Core\Http\Middleware;

use AdminFlow\Core\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    /**
     * 处理进入后台受保护路由的请求
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->hasHeader('HX-Request')) {
                return response('')->header('HX-Redirect', route('admin.login'));
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('admin.login'));
        }

        Auth::shouldUse('admin');

        $user = Auth::guard('admin')->user();

        // 会话有效性与账号状态二次核验：若账号在会话期间被管理员停用，立即强退
        if (!$user instanceof User || !$user->isActive()) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', new MessageBag(['email' => [
                    '您的账号已被停用，已强制退出系统',
                ]]));
                $request->session()->flash('errors', $errorBag);

                return response('')->header('HX-Redirect', route('admin.login'));
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => '账号已被停用'], 403);
            }

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'email' => '您的账号已被停用，已强制退出系统',
                ]);
        }

        // 若当前请求是由有效「记住登录」Cookie 恢复重构的会话，且已通过框架 Recaller 密码令牌验签：
        // 自动将当前密码哈希补入新会话，确保免登与会话存续正常
        if (Auth::guard('admin')->viaRemember()) {
            $request->session()->put('password_hash_admin', $user->getAuthPassword());
        }

        // 密码哈希一致性安全校验：既有会话必须携带且严格匹配当前密码哈希；缺少标记或哈希不匹配时，强制踢出并要求重新登录
        $storedHash = $request->session()->get('password_hash_admin');
        $currentHash = $user->getAuthPassword();

        if (!is_string($storedHash) || !hash_equals($currentHash, $storedHash)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', new MessageBag(['email' => [
                    '登录凭证已失效或密码已变更，请重新登录',
                ]]));
                $request->session()->flash('errors', $errorBag);

                return response('')->header('HX-Redirect', route('admin.login'));
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => '登录凭证已失效或密码已变更，请重新登录'], 401);
            }

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'email' => '登录凭证已失效或密码已变更，请重新登录',
                ]);
        }

        return $next($request);
    }
}
