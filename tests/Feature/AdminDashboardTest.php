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

    public function test_sidebar_renders_registered_navigation_items_with_permission_filtering(): void
    {
        $admin = User::query()->create([
            'name' => '超管导航员',
            'email' => 'nav_admin@example.com',
            'password' => Hash::make('Pass123456'),
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertOk();
        // 验证由 CMS 动态注册的菜单成功渲染在侧栏中，且包含真实的 CMS 写作路由
        $response->assertSee('创作工坊 (CMS 插件)');
        $response->assertSee('Markdown 文章写作');
        $response->assertSee(route('admin.cms.posts.index'));
    }

    public function test_guest_calling_navigation_registry_directly_is_denied_for_protected_items(): void
    {
        auth()->logout();

        /** @var \AdminFlow\Core\Services\NavigationRegistry $registry */
        $registry = app(\AdminFlow\Core\Services\NavigationRegistry::class);

        $visibleSections = $registry->getVisibleSections();

        // 验证受权限保护的分组在访客调用时被完全过滤，无可见项
        $this->assertEmpty($visibleSections);
        foreach ($visibleSections as $section) {
            $this->assertNotSame('cms', $section['key']);
            foreach ($section['items'] as $item) {
                $this->assertNotSame('Markdown 文章写作', $item['title']);
            }
        }
    }
}
