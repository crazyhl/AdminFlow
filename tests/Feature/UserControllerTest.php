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
}
