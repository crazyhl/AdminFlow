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
}
