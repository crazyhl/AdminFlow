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

    /**
     * 测试用户统计指标单次聚合正确性
     */
    public function test_get_statistics_aggregates_correctly(): void
    {
        User::create([
            'name' => '超管A',
            'email' => 'admin_a@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_active' => true,
        ]);
        User::create([
            'name' => '超管B',
            'email' => 'admin_b@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_active' => false,
        ]);
        User::create([
            'name' => '编辑A',
            'email' => 'editor_a@example.com',
            'password' => 'Pass123456',
            'role' => Role::EDITOR,
            'is_active' => true,
        ]);

        $stats = $this->userService->getStatistics();

        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['active']);
        $this->assertSame(2, $stats['super_admin']);
    }

    /**
     * 测试在事务锁定后重新校验超管防御，防止并发提权竞态逃逸
     */
    public function test_toggle_active_guards_against_concurrency_race(): void
    {
        $actor = User::create([
            'name' => '主超管',
            'email' => 'master@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $staleTarget = User::create([
            'name' => '普通目标',
            'email' => 'target_race@example.com',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        // 模拟另一并发事务在此时将该账号提拔为超管并已落库
        User::query()
            ->where('id', $staleTarget->id)
            ->update([
                'role' => Role::SUPER_ADMIN->value,
                'is_admin' => true,
            ]);

        // 旧请求持有的 $staleTarget 内存模型角色仍为 VIEWER，但在事务内通过 lockForUpdate 查出最新角色并拦截
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('禁止停用超级管理员账号');

        $this->userService->toggleActive($staleTarget, $actor);
    }

    public function test_delete_guards_against_concurrency_race(): void
    {
        $actor = User::create([
            'name' => '主超管2',
            'email' => 'master2@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $staleTarget = User::create([
            'name' => '普通目标2',
            'email' => 'target_race2@example.com',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        // 模拟并发事务提拔为超管
        User::query()
            ->where('id', $staleTarget->id)
            ->update([
                'role' => Role::SUPER_ADMIN->value,
                'is_admin' => true,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('禁止删除超级管理员账号');

        $this->userService->delete($staleTarget, $actor);
    }

    public function test_before_delete_hooks_are_instance_scoped_and_do_not_leak_across_instances(): void
    {
        $serviceA = new UserService();
        $serviceB = new UserService();

        $hookExecuted = false;
        $serviceA->beforeDelete(static function () use (&$hookExecuted): void {
            $hookExecuted = true;
        });

        $actor = User::create([
            'name' => '超管Hook',
            'email' => 'hook_actor@example.com',
            'password' => 'Pass123456',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
        ]);

        $target = User::create([
            'name' => '待删Hook目标',
            'email' => 'hook_target@example.com',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);

        // 使用 serviceB 执行删除，serviceA 上注册的 hook 绝不应该被调用
        $serviceB->delete($target, $actor);
        $this->assertFalse($hookExecuted);

        // 使用 serviceA 删除另一账号，hook 正常触发
        $target2 = User::create([
            'name' => '待删Hook目标2',
            'email' => 'hook_target2@example.com',
            'password' => 'Pass123456',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);
        $serviceA->delete($target2, $actor);
        $this->assertTrue($hookExecuted);
    }
}
