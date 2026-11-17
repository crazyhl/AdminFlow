<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => '超级管理员',
            'email' => 'admin@adminflow.test',
            'password' => 'AdminPass2026',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_viewer_without_permission_is_forbidden(): void
    {
        $viewer = User::create([
            'name' => '普通访客',
            'email' => 'viewer@adminflow.test',
            'password' => 'ViewerPass2026',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($viewer, 'admin')->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('成员与权限管理');
        $response->assertSee('admin@adminflow.test');
        $response->assertSee('超级管理员');
    }

    public function test_user_filtering_by_keyword_and_role(): void
    {
        User::create([
            'name' => '李编辑',
            'email' => 'editor_li@adminflow.test',
            'password' => 'EditorPass2026',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        User::create([
            'name' => '王访客',
            'email' => 'viewer_wang@adminflow.test',
            'password' => 'ViewerPass2026',
            'role' => Role::VIEWER,
            'is_active' => false,
        ]);

        // 搜索关键字
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.users.index', ['search' => '李编辑']));
        $response->assertSee('李编辑');
        $response->assertDontSee('王访客');

        // 筛选角色
        $responseRole = $this->actingAs($this->admin, 'admin')->get(route('admin.users.index', ['role' => 'editor']));
        $responseRole->assertSee('李编辑');
        $responseRole->assertDontSee('王访客');

        // 筛选状态
        $responseActive = $this->actingAs($this->admin, 'admin')->get(route('admin.users.index', ['is_active' => '0']));
        $responseActive->assertSee('王访客');
        $responseActive->assertDontSee('李编辑');
    }

    public function test_create_user_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.store'), [
            'name' => '新协同者',
            'email' => 'new_collaborator@adminflow.test',
            'password' => 'CollabPass2026',
            'role' => Role::EDITOR->value,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('adminflow_users', [
            'email' => 'new_collaborator@adminflow.test',
            'name' => '新协同者',
            'role' => 'editor',
            'is_active' => true,
        ]);

        $created = User::query()->where('email', 'new_collaborator@adminflow.test')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('CollabPass2026', $created->password));

        // 验证审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'user.create',
            'target_id' => (string) $created->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_create_user_rejects_weak_password(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.store'), [
            'name' => '弱口令用户',
            'email' => 'weak@adminflow.test',
            'password' => '12345678',
            'role' => Role::EDITOR->value,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('adminflow_users', ['email' => 'weak@adminflow.test']);
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.store'), [
            'name' => '重复邮箱用户',
            'email' => 'admin@adminflow.test',
            'password' => 'SafePass2026',
            'role' => Role::EDITOR->value,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_update_user_profile_and_role(): void
    {
        $target = User::create([
            'name' => '原姓名',
            'email' => 'before_edit@adminflow.test',
            'password' => 'OriginalPass2026',
            'role' => Role::VIEWER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->put(route('admin.users.update', $target), [
            'name' => '更新后姓名',
            'email' => 'after_edit@adminflow.test',
            'role' => Role::EDITOR->value,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('更新后姓名', $target->name);
        $this->assertSame('after_edit@adminflow.test', $target->email);
        $this->assertSame(Role::EDITOR, $target->role);

        // 验证审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'user.update',
            'target_id' => (string) $target->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_toggle_active_status_and_self_disable_guard(): void
    {
        $target = User::create([
            'name' => '待停用用户',
            'email' => 'active_target@adminflow.test',
            'password' => 'TargetPass2026',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        // 成功停用
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.toggle-active', $target));
        $response->assertSessionHas('success');
        $this->assertFalse($target->fresh()->is_active);

        // 再次调用启用
        $response2 = $this->actingAs($this->admin, 'admin')->post(route('admin.users.toggle-active', $target));
        $response2->assertSessionHas('success');
        $this->assertTrue($target->fresh()->is_active);

        // 防御性策略：禁止禁用自身
        $responseSelf = $this->actingAs($this->admin, 'admin')->post(route('admin.users.toggle-active', $this->admin));
        $responseSelf->assertForbidden();
    }

    public function test_reset_password_successfully(): void
    {
        $target = User::create([
            'name' => '重置密码目标',
            'email' => 'reset_pwd@adminflow.test',
            'password' => 'OldPassword2026',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.reset-password', $target), [
            'password' => 'NewStrongPass2026',
            'password_confirmation' => 'NewStrongPass2026',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewStrongPass2026', $target->fresh()->password));

        // 验证审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'user.reset_password',
            'target_id' => (string) $target->id,
        ]);

        // 测试 JSON 异步提交重置成功场景
        $jsonResponse = $this->actingAs($this->admin, 'admin')->postJson(route('admin.users.reset-password', $target), [
            'password' => 'AnotherStrongPass2026',
            'password_confirmation' => 'AnotherStrongPass2026',
        ]);

        $jsonResponse->assertOk();
        $jsonResponse->assertJson([
            'success' => true,
        ]);
        $this->assertTrue(Hash::check('AnotherStrongPass2026', $target->fresh()->password));
    }

    public function test_reset_password_rejects_mismatched_confirmation(): void
    {
        $target = User::create([
            'name' => '不匹配目标',
            'email' => 'mismatch@adminflow.test',
            'password' => 'OldPassword2026',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.reset-password', $target), [
            'password' => 'NewStrongPass2026',
            'password_confirmation' => 'DifferentPassword2026',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('OldPassword2026', $target->fresh()->password));
        $this->assertDatabaseMissing('adminflow_audit_logs', [
            'action' => 'user.reset_password',
            'target_id' => (string) $target->id,
        ]);

        // 测试 JSON 异步提交场景 (422 无刷新内嵌报错)
        $jsonResponse = $this->actingAs($this->admin, 'admin')->postJson(route('admin.users.reset-password', $target), [
            'password' => 'NewStrongPass2026',
            'password_confirmation' => 'DifferentPassword2026',
        ]);

        $jsonResponse->assertUnprocessable();
        $jsonResponse->assertJsonValidationErrors(['password']);
        $this->assertSame('两次输入的新密码不一致，请重新核对', $jsonResponse->json('errors.password.0'));
    }

    public function test_delete_user_and_self_delete_guard(): void
    {
        $target = User::create([
            'name' => '待删除用户',
            'email' => 'to_delete@adminflow.test',
            'password' => 'DeletePass2026',
            'role' => Role::VIEWER,
            'is_active' => true,
        ]);

        // 成功删除
        $response = $this->actingAs($this->admin, 'admin')->delete(route('admin.users.destroy', $target));
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('adminflow_users', ['id' => $target->id]);

        // 防御性策略：禁止删除自己
        $responseSelf = $this->actingAs($this->admin, 'admin')->delete(route('admin.users.destroy', $this->admin));
        $responseSelf->assertForbidden();
    }

    public function test_htmx_create_user_success_triggers_toast(): void
    {
        $response = $this
            ->actingAs($this->admin, 'admin')
            ->withHeader('HX-Request', 'true')
            ->post(route('admin.users.store'), [
                'name' => 'HTMX成员',
                'email' => 'htmx@adminflow.test',
                'password' => 'HtmxPass2026',
                'role' => Role::EDITOR->value,
                'is_active' => '1',
            ]);

        $response->assertOk();
        $response->assertHeader('HX-Refresh', 'true');
        $response->assertSessionHas('success');
        $this->assertStringContainsString('showToast', (string) $response->headers->get('HX-Trigger'));
        $this->assertDatabaseHas('adminflow_users', ['email' => 'htmx@adminflow.test']);
    }

    public function test_htmx_create_user_validation_error_renders_create_form_partial(): void
    {
        $response = $this
            ->actingAs($this->admin, 'admin')
            ->withHeader('HX-Request', 'true')
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'invalid-email',
                'password' => 'short',
                'role' => 'invalid-role',
            ]);

        $response->assertOk();
        $response->assertViewIs('adminflow-core::users.partials.create-form');
        $response->assertSee('请输入成员姓名');
        $response->assertSee('邮箱格式不符合规范');
    }

    public function test_htmx_update_user_validation_error_renders_edit_form_partial(): void
    {
        $target = User::create([
            'name' => '原有用户',
            'email' => 'existing@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->withHeader('HX-Request', 'true')->put(
            route('admin.users.update', $target),
            [
                'name' => '',
                'email' => 'admin@adminflow.test', // 占用超管邮箱
                'role' => Role::VIEWER->value,
            ],
        );

        $response->assertOk();
        $response->assertViewIs('adminflow-core::users.partials.edit-form');
        $response->assertSee('该邮箱已被其他成员占用');
    }

    public function test_htmx_reset_password_success_triggers_toast(): void
    {
        $target = User::create([
            'name' => '密码目标',
            'email' => 'pwd_target@adminflow.test',
            'password' => 'OldPass123456',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->withHeader('HX-Request', 'true')
            ->post(route('admin.users.reset-password', $target), [
                'password' => 'NewStrongPass2026',
                'password_confirmation' => 'NewStrongPass2026',
            ]);

        $response->assertOk();
        $response->assertHeader('HX-Refresh', 'true');
        $response->assertSessionHas('success');
        $this->assertStringContainsString('showToast', (string) $response->headers->get('HX-Trigger'));
        $this->assertTrue(Hash::check('NewStrongPass2026', $target->fresh()->password));
    }

    public function test_htmx_update_user_success_triggers_toast(): void
    {
        $target = User::create([
            'name' => '待更新目标',
            'email' => 'to_update@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->withHeader('HX-Request', 'true')->put(
            route('admin.users.update', $target),
            [
                'name' => '已改名成员',
                'email' => 'updated_email@adminflow.test',
                'role' => Role::EDITOR->value,
            ],
        );

        $response->assertOk();
        $response->assertHeader('HX-Refresh', 'true');
        $response->assertSessionHas('success');
        $this->assertStringContainsString('showToast', (string) $response->headers->get('HX-Trigger'));
        $this->assertSame('已改名成员', $target->fresh()->name);
    }

    public function test_htmx_reset_password_mismatched_renders_partial_with_errors(): void
    {
        $target = User::create([
            'name' => '密码目标2',
            'email' => 'pwd_target2@adminflow.test',
            'password' => 'OldPass123456',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->withHeader('HX-Request', 'true')
            ->post(route('admin.users.reset-password', $target), [
                'password' => 'NewStrongPass2026',
                'password_confirmation' => 'MismatchedPass2026',
            ]);

        $response->assertOk();
        $response->assertViewIs('adminflow-core::users.partials.reset-password-form');
        $response->assertSee('两次输入的新密码不一致，请重新核对');
    }

    /**
     * 测试普通用户编辑自己时，无法通过篡改 role 越权升为超级管理员
     */
    public function test_user_cannot_escalate_role_by_editing_self(): void
    {
        $editor = User::create([
            'name' => '普通编辑员',
            'email' => 'editor_esc@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::EDITOR,
            'is_admin' => false,
            'is_active' => true,
        ]);

        // 尝试通过编辑自己的资料把角色改为 super_admin
        $response = $this->actingAs($editor, 'admin')->put(route('admin.users.update', $editor), [
            'name' => '普通编辑员',
            'email' => 'editor_esc@adminflow.test',
            'role' => Role::SUPER_ADMIN->value,
        ]);

        $response->assertForbidden();
        $this->assertSame(Role::EDITOR, $editor->fresh()->role);
        $this->assertFalse($editor->fresh()->is_admin);
    }

    /**
     * 测试取消勾选 is_active 后，新创建的用户确为停用状态 (is_active = false)
     */
    public function test_create_user_with_unchecked_is_active_creates_disabled_user(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.users.store'), [
            'name' => '待停用成员',
            'email' => 'disabled_user@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::VIEWER->value,
            'is_active' => '0', // 模拟表单未勾选或显式提交 0
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $created = User::query()->where('email', 'disabled_user@adminflow.test')->firstOrFail();
        $this->assertFalse($created->is_active);
    }

    /**
     * 测试未授权用户无法访问布局预览路由
     */
    public function test_preview_layout_requires_view_any_permission(): void
    {
        $viewer = User::create([
            'name' => '无权访客',
            'email' => 'noperm_preview@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($viewer, 'admin')->get(route('admin.preview.layout-a'))->assertForbidden();
        $this->actingAs($viewer, 'admin')->get(route('admin.preview.layout-b'))->assertForbidden();
    }
}
