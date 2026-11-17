<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => '审计超管',
            'email' => 'audit_admin@adminflow.test',
            'password' => 'AuditAdminPass2026',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.audit-logs.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_viewer_without_permission_is_forbidden(): void
    {
        $viewer = User::create([
            'name' => '只读访客',
            'email' => 'viewer_audit@adminflow.test',
            'password' => 'ViewerPass2026',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer, 'admin')->get(route('admin.audit-logs.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_audit_logs_with_chinese_labels(): void
    {
        AuditLogger::record('user.create', target: $this->admin, details: ['created_name' => '新成员测试']);
        AuditLogger::record('auth.login_success', target: $this->admin, details: ['ip' => '127.0.0.1']);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('操作审计日志');
        $response->assertSee('不可变存证');
        // 验证中文化模块与操作
        $response->assertSee('成员管理');
        $response->assertSee('创建新成员');
        $response->assertSee('身份认证');
        $response->assertSee('登录成功');
    }

    public function test_audit_logs_filter_by_module_and_search(): void
    {
        AuditLogger::record('user.create', target: $this->admin, details: ['tag' => 'user_test_action']);
        AuditLogger::record('auth.login_success', target: $this->admin, details: ['tag' => 'auth_test_action']);

        // 仅筛选 user 模块
        $responseUser = $this->actingAs($this->admin, 'admin')->get(route('admin.audit-logs.index', [
            'module' => 'user',
        ]));
        $responseUser->assertSee('创建新成员');
        $responseUser->assertDontSee('auth.login_success');

        // 仅筛选 auth 模块
        $responseAuth = $this->actingAs($this->admin, 'admin')->get(route('admin.audit-logs.index', [
            'module' => 'auth',
        ]));
        $responseAuth->assertSee('登录成功');
        $responseAuth->assertDontSee('user.create');
    }

    public function test_audit_logs_pagination_and_per_page(): void
    {
        for ($i = 0; $i < 12; $i++) {
            AuditLogger::record('user.create', target: $this->admin, details: ['index' => $i]);
        }

        // 默认 per_page=10 应该产生分页
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.audit-logs.index', ['per_page' => 10]));
        $response->assertOk();
        $response->assertSee('第 1 / 2 页');
        $response->assertSee('每页显示:');

        // 第 2 页
        $responsePage2 = $this->actingAs($this->admin, 'admin')->get(route('admin.audit-logs.index', [
            'per_page' => 10,
            'page' => 2,
        ]));
        $responsePage2->assertOk();
        $responsePage2->assertSee('第 2 / 2 页');
    }
}
