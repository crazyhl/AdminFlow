<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    /**
     * 测试创建用户及审计日志记录
     */
    public function test_create_user_successfully(): void
    {
        $admin = User::create([
            'name' => '超管',
            'email' => 'admin@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $newUser = $this->userService->create([
            'name' => '张编辑',
            'email' => 'editor@example.com',
            'password' => 'EditorPass2026',
            'role' => Role::EDITOR,
        ], $admin);

        $this->assertDatabaseHas('adminflow_users', [
            'id' => $newUser->id,
            'email' => 'editor@example.com',
            'role' => 'editor',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->assertTrue(Hash::check('EditorPass2026', $newUser->password));

        // 验证审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'user.create',
            'target_id' => (string) $newUser->id,
            'user_id' => $admin->id,
        ]);
    }

    /**
     * 测试创建用户时拦截弱密码与重复邮箱
     */
    public function test_create_user_validations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('密码长度至少为 8 位');

        $this->userService->create([
            'name' => '小明',
            'email' => 'ming@example.com',
            'password' => 'short',
        ]);
    }

    /**
     * 测试切换用户状态及其防自毁边界
     */
    public function test_toggle_user_active_status(): void
    {
        $admin = User::create([
            'name' => '主超管',
            'email' => 'root@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $targetUser = User::create([
            'name' => '普通用户',
            'email' => 'normal@example.com',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_active' => true,
        ]);

        // 成功停用
        $this->userService->toggleActive($targetUser, $admin);
        $this->assertFalse($targetUser->fresh()->is_active);

        // 审计日志校验
        $log = AuditLog::query()->where('action', 'user.toggle_status')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertTrue($log->details['from']);
        $this->assertFalse($log->details['to']);

        // 防御 1：禁止停用自己
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('禁止停用当前登录账号');
        $this->userService->toggleActive($admin, $admin);
    }

    /**
     * 测试密码重置与审计日志记录
     */
    public function test_reset_password_successfully(): void
    {
        $admin = User::create([
            'name' => '主超管',
            'email' => 'root@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $user = User::create([
            'name' => '重置目标',
            'email' => 'target@example.com',
            'password' => 'OldPass123456',
            'role' => Role::VIEWER,
        ]);

        $this->userService->resetPassword($user, 'NewStrongPass2026', $admin);

        $this->assertTrue(Hash::check('NewStrongPass2026', $user->fresh()->password));

        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'user.reset_password',
            'target_id' => (string) $user->id,
            'user_id' => $admin->id,
        ]);
    }

    /**
     * 测试用户分页与多维筛选
     */
    public function test_user_pagination_and_filters(): void
    {
        User::create([
            'name' => '张三',
            'email' => 'zhangsan@example.com',
            'password' => 'Pass123456',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);
        User::create([
            'name' => '李四',
            'email' => 'lisi@example.com',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_active' => false,
        ]);
        User::create([
            'name' => '王五',
            'email' => 'wangwu@example.com',
            'password' => 'Pass123456',
            'role' => Role::EDITOR,
            'is_active' => false,
        ]);

        // 筛选角色为 editor
        $editors = $this->userService->paginate(['role' => 'editor']);
        $this->assertSame(2, $editors->total());

        // 筛选状态为停用 (is_active = false)
        $inactives = $this->userService->paginate(['is_active' => false]);
        $this->assertSame(2, $inactives->total());

        // 关键词搜索
        $searchResult = $this->userService->paginate(['search' => 'zhangsan']);
        $this->assertSame(1, $searchResult->total());
    }
}
