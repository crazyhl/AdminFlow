<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminInitCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试中文环境下的管理员初始化成功流程
     */
    public function test_admin_init_success_in_chinese(): void
    {
        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsOutput('=== AdminFlow 超级管理员账号初始化 ===')
            ->expectsQuestion('请输入管理员姓名/昵称', '系统超管')
            ->expectsQuestion('请输入管理员邮箱地址', 'admin@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', 'Secr3tP@ss2026')
            ->expectsQuestion('请再次输入密码以确认', 'Secr3tP@ss2026')
            ->expectsOutput('超级管理员账号 [admin@example.com] 创建成功！')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('系统超管', $admin->name);
        $this->assertTrue($admin->is_admin);
        $this->assertSame(Role::SUPER_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('Secr3tP@ss2026', $admin->password));
    }

    /**
     * 测试英文环境下的管理员初始化成功流程
     */
    public function test_admin_init_success_in_english(): void
    {
        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'en'])
            ->expectsOutput('=== AdminFlow Super Administrator Initialization ===')
            ->expectsQuestion('Please enter the administrator name', 'Super Admin')
            ->expectsQuestion('Please enter the administrator email address', 'admin.en@example.com')
            ->expectsQuestion('Please enter the password (min 8 characters, letters & numbers required)', 'EnSecr3t999')
            ->expectsQuestion('Please confirm the password', 'EnSecr3t999')
            ->expectsOutput('Super administrator [admin.en@example.com] created successfully!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('adminflow_users', [
            'email' => 'admin.en@example.com',
            'role' => 'super_admin',
        ]);
    }

    /**
     * 测试已存在管理员时被拒绝拦截（未加 --force）
     */
    public function test_admin_init_prevents_duplicate_without_force(): void
    {
        User::create([
            'name' => 'Existing Admin',
            'email' => 'existing@example.com',
            'password' => 'secret123456',
            'is_admin' => true,
            'role' => Role::SUPER_ADMIN,
        ]);

        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsOutput('系统中已存在超级管理员账号 [existing@example.com]。如需继续创建，请加上 --force 参数。')
            ->assertExitCode(1);
    }

    /**
     * 测试使用 --force 允许重置已有管理员密码与信息，并轮换记住令牌与撤销既有会话
     */
    public function test_admin_init_can_reset_existing_admin_with_force(): void
    {
        $existing = User::create([
            'name' => 'Existing Admin',
            'email' => 'existing@example.com',
            'password' => Hash::make('OldPassword123'),
            'is_admin' => true,
            'role' => Role::SUPER_ADMIN,
        ]);
        $existing->forceFill(['remember_token' => 'old_remember_token_string_12345'])->save();

        DB::table('sessions')->insert([
            'id' => 'session_admin_test_1',
            'user_id' => $existing->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'serialized-payload',
            'last_activity' => time(),
        ]);

        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'zh_CN', '--force' => true])
            ->expectsQuestion('请输入管理员姓名/昵称', '新管理员姓名')
            ->expectsQuestion('请输入管理员邮箱地址', 'existing@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', 'NewSecr3t2026')
            ->expectsQuestion('请再次输入密码以确认', 'NewSecr3t2026')
            ->expectsOutput('超级管理员账号 [existing@example.com] 创建成功！')
            ->assertExitCode(0);

        $existing->refresh();
        $this->assertSame('新管理员姓名', $existing->name);
        $this->assertTrue(Hash::check('NewSecr3t2026', $existing->password));
        $this->assertNotSame('old_remember_token_string_12345', $existing->remember_token);
        $this->assertNotNull($existing->remember_token);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $existing->id)->count());
    }

    /**
     * 测试使用 --force 时拒绝匹配普通成员邮箱，防止提升与重新启用普通账号
     */
    public function test_admin_init_with_force_rejects_non_super_admin_user(): void
    {
        $member = User::create([
            'name' => 'Regular Member',
            'email' => 'member@example.com',
            'password' => Hash::make('MemberPass123'),
            'is_admin' => false,
            'is_active' => false,
            'role' => Role::EDITOR,
        ]);

        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'zh_CN', '--force' => true])
            ->expectsQuestion('请输入管理员姓名/昵称', '试图提权')
            ->expectsQuestion('请输入管理员邮箱地址', 'member@example.com')
            ->expectsOutput('该邮箱已被普通成员使用，密码重置仅作用于已有超级管理员。')
            ->assertExitCode(1);

        $member->refresh();
        $this->assertFalse($member->is_admin);
        $this->assertFalse($member->is_active);
        $this->assertSame(Role::EDITOR, $member->role);
        $this->assertSame('Regular Member', $member->name);
    }

    /**
     * 测试英文环境下 --force 拦截普通成员账号
     */
    public function test_admin_init_with_force_rejects_non_super_admin_user_in_english(): void
    {
        User::create([
            'name' => 'Viewer Member',
            'email' => 'viewer@example.com',
            'password' => Hash::make('ViewerPass123'),
            'is_admin' => false,
            'is_active' => true,
            'role' => Role::VIEWER,
        ]);

        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'en', '--force' => true])
            ->expectsQuestion('Please enter the administrator name', 'Hacker')
            ->expectsQuestion('Please enter the administrator email address', 'viewer@example.com')
            ->expectsOutput(
                'This email belongs to a standard user. Password reset is restricted to existing super administrators.',
            )
            ->assertExitCode(1);
    }

    /**
     * 测试弱密码被拒绝拦截
     */
    public function test_admin_init_rejects_weak_password(): void
    {
        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsQuestion('请输入管理员姓名/昵称', '超管')
            ->expectsQuestion('请输入管理员邮箱地址', 'test@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', '12345678')
            ->expectsOutput('密码过于简单，必须同时包含字母和数字，且不能为常见弱密码。')
            ->assertExitCode(1);
    }

    /**
     * 测试两次密码不一致被拦截
     */
    public function test_admin_init_rejects_mismatched_passwords(): void
    {
        $this
            ->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsQuestion('请输入管理员姓名/昵称', '超管')
            ->expectsQuestion('请输入管理员邮箱地址', 'test@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', 'Str0ngPass123')
            ->expectsQuestion('请再次输入密码以确认', 'DifferentPass456')
            ->expectsOutput('两次输入的密码不一致，请重新操作。')
            ->assertExitCode(1);
    }
}
