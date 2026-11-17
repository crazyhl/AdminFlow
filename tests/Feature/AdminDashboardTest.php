<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试已登录管理员访问控制台仪表盘
     */
    public function test_authenticated_admin_can_view_dashboard(): void
    {
        $admin = User::query()->create([
            'name' => '北欧体验员',
            'email' => 'nordic@example.com',
            'password' => Hash::make('Pass123456'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'auth.login_success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestBrowser',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('午后好，系统运行平稳。');
        $response->assertSee('控制台仪表盘');
        $response->assertSee('北欧体验员');
        $response->assertSee('auth.login_success');
        $response->assertSee('setTheme');
        $response->assertSee('theme-btn-system');
        $response->assertSee('toggleSidebarMini');
        $response->assertSee('hs-application-sidebar');
    }

    /**
     * 测试无审计权限的用户访问 Dashboard 时不泄露审计流水
     */
    public function test_viewer_without_audit_permission_cannot_see_audit_logs_in_dashboard(): void
    {
        $viewer = User::query()->create([
            'name' => '普通访客',
            'email' => 'viewer@example.com',
            'password' => Hash::make('Pass123456'),
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        AuditLog::query()->create([
            'user_id' => $viewer->id,
            'action' => 'sensitive.super_secret_action',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'SecretAgent',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('sensitive.super_secret_action');
        $response->assertDontSee('192.168.1.100');
        $response->assertSee('受限');
    }
}
