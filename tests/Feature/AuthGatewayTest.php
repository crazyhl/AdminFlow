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
}
