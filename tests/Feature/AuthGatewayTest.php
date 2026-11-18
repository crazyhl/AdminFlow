<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthGatewayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试未登录访问受保护后台首页时重定向至登录页
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * 测试展示登录页面
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get(route('admin.login'));
        $response->assertOk();
        $response->assertSee('AdminFlow 登录');
    }

    /**
     * 测试正常凭证登录成功、会话重建及审计日志
     */
    public function test_login_success(): void
    {
        $user = User::query()->create([
            'name' => '管理员',
            'email' => 'admin@example.com',
            'password' => Hash::make('Secr3tPass2026'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'Secr3tPass2026',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($user->id, Auth::guard('admin')->id());

        // 验证真实 Session 下访问受 Gate 保护的用户管理路由不会报 403
        $usersResponse = $this->get(route('admin.users.index'));
        $usersResponse->assertOk();
        $usersResponse->assertSee('成员与权限管理');

        // 验证登录审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'auth.login_success',
            'user_id' => $user->id,
        ]);
    }

    /**
     * 测试错误密码登录被拦截及审计日志记录
     */
    public function test_login_with_invalid_password_fails(): void
    {
        User::query()->create([
            'name' => '管理员',
            'email' => 'admin@example.com',
            'password' => Hash::make('CorrectPass2026'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'WrongPass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('admin')->check());

        // 验证失败审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'auth.login_failed',
        ]);
    }

    /**
     * 测试已停用账号禁止登录
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::query()->create([
            'name' => '已停用用户',
            'email' => 'inactive@example.com',
            'password' => Hash::make('CorrectPass2026'),
            'role' => Role::EDITOR,
            'is_active' => false,
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => 'inactive@example.com',
            'password' => 'CorrectPass2026',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('admin')->check());

        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'auth.login_rejected',
        ]);
    }

    /**
     * 测试防爆破限流拦截（连续 5 次错误触发锁定）
     */
    public function test_login_rate_limiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.submit'), [
                'email' => 'brute_force@example.com',
                'password' => 'wrong',
            ]);
        }

        // 第 6 次请求触发限流拦截
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'brute_force@example.com',
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'auth.login_locked',
        ]);
    }

    /**
     * 测试会话期间账号被停用时的安全踢出
     */
    public function test_active_session_kicked_when_user_disabled(): void
    {
        $user = User::query()->create([
            'name' => '编辑员',
            'email' => 'editor@example.com',
            'password' => Hash::make('Pass123456'),
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        // 登录状态访问后台正常
        $this->actingAs($user, 'admin');
        $response = $this->get('/admin');
        $response->assertOk();

        // 模拟后台管理员停用此账号
        $user->is_active = false;
        $user->save();

        // 再次访问时被中间件强制登出并重定向
        $responseAfterDisable = $this->get('/admin');
        $responseAfterDisable->assertRedirect(route('admin.login'));
        $this->assertFalse(Auth::guard('admin')->check());
    }

    /**
     * 测试注销登录
     */
    public function test_logout_successfully(): void
    {
        $user = User::query()->create([
            'name' => '注销测试员',
            'email' => 'logout@example.com',
            'password' => Hash::make('Pass123456'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin');
        $this->assertTrue(Auth::guard('admin')->check());

        $response = $this->post(route('admin.logout'));
        $response->assertRedirect(route('admin.login'));
        $this->assertFalse(Auth::guard('admin')->check());

        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->id,
        ]);
    }

    /**
     * 测试已停用账号发起 HTMX 请求时，返回 HX-Redirect 并写入 email 错误消息包
     */
    public function test_disabled_user_htmx_request_returns_hx_redirect_with_errors(): void
    {
        $user = User::query()->create([
            'name' => '已停用HTMX用户',
            'email' => 'disabled_htmx@example.com',
            'password' => Hash::make('Pass123456'),
            'role' => Role::EDITOR,
            'is_active' => false,
        ]);

        $this->actingAs($user, 'admin');

        $response = $this->withHeader('HX-Request', 'true')->get('/admin');

        $response->assertHeader('HX-Redirect', route('admin.login'));
        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('admin')->check());
    }

    /**
     * 测试管理员密码变更后，既有会话在中间件核验时自动失效强制登出
     */
    public function test_session_invalidated_when_password_hash_changes(): void
    {
        $user = User::query()->create([
            'name' => '改密测试员',
            'email' => 'pwd_change@example.com',
            'password' => Hash::make('OldSecr3t2026'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        // 正常登录建立会话，写入 password_hash_admin
        $loginResponse = $this->post(route('admin.login.submit'), [
            'email' => 'pwd_change@example.com',
            'password' => 'OldSecr3t2026',
        ]);
        $loginResponse->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(Auth::guard('admin')->check());

        // 访问后台正常
        $dashboardResponse = $this->get('/admin');
        $dashboardResponse->assertOk();

        // 模拟通过命令行或后台重置了密码
        $user->forceFill([
            'password' => Hash::make('BrandNewP@ss2026'),
        ])->save();

        // 模拟跨请求场景：刷新测试进程单例 Guard 中的用户模型（使其反映 DB 中的最新改密结果）
        Auth::guard('admin')->setUser($user->fresh());

        // 使用已有会话继续请求后台，中间件比对密码哈希不一致，执行安全登出
        $subsequentResponse = $this->get('/admin');
        $subsequentResponse->assertRedirect(route('admin.login'));
        $this->assertFalse(Auth::guard('admin')->check());
    }

    /**
     * 测试缺少 password_hash_admin 标记的历史会话被强制拦截登出
     */
    public function test_legacy_session_without_password_hash_marker_is_rejected(): void
    {
        $user = User::query()->create([
            'name' => '历史会话用户',
            'email' => 'legacy_session@example.com',
            'password' => Hash::make('Secr3tPass2026'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        $guardKey = Auth::guard('admin')->getName();

        // 模拟缺少 password_hash_admin 标记的既有真实历史会话
        $response = $this->withSession([
            $guardKey => $user->id,
        ])->get('/admin');

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors(['email' => '登录凭证已失效或密码已变更，请重新登录']);
        $this->assertFalse(Auth::guard('admin')->check());
    }

    /**
     * 测试有效「记住登录」Cookie 可以成功登入并自动补齐密码哈希会话标记
     */
    public function test_remember_me_cookie_authenticates_and_sets_password_hash_marker(): void
    {
        $user = User::query()->create([
            'name' => '记住我用户',
            'email' => 'remember_me@example.com',
            'password' => Hash::make('RememberP@ss2026'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        // 勾选记住我登录
        $loginResponse = $this->post(route('admin.login.submit'), [
            'email' => 'remember_me@example.com',
            'password' => 'RememberP@ss2026',
            'remember' => '1',
        ]);
        $loginResponse->assertRedirect(route('admin.dashboard'));

        // 获取记住登录 Cookie
        $recallerCookieName = Auth::guard('admin')->getRecallerName();
        $loginResponse->assertCookie($recallerCookieName);
        $cookie = $loginResponse->getCookie($recallerCookieName);

        // 模拟清空 Session，仅携带记住登录 Cookie 发起新的请求
        $this->flushSession();
        Auth::guard('admin')->forgetUser();

        $response = $this->withCookie($recallerCookieName, $cookie->getValue())->get('/admin');
        $response->assertOk();
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($user->getAuthPassword(), session('password_hash_admin'));
    }
}
