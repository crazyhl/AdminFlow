<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsOutput('=== AdminFlow 超级管理员账号初始化 ===')
            ->expectsQuestion('请输入管理员姓名/昵称', '系统超管')
            ->expectsQuestion('请输入管理员邮箱地址', 'admin@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', 'Secr3tP@ss2026')
            ->expectsQuestion('请再次输入密码以确认', 'Secr3tP@ss2026')
            ->expectsOutput('超级管理员账号 [admin@example.com] 创建成功！')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin@example.com')->first();
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
        $this->artisan('adminflow:admin-init', ['--locale' => 'en'])
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

        $this->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsOutput('系统中已存在超级管理员账号 [existing@example.com]。如需继续创建，请加上 --force 参数。')
            ->assertExitCode(1);
    }

    /**
     * 测试弱密码被拒绝拦截
     */
    public function test_admin_init_rejects_weak_password(): void
    {
        $this->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
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
        $this->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsQuestion('请输入管理员姓名/昵称', '超管')
            ->expectsQuestion('请输入管理员邮箱地址', 'test@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', 'Str0ngPass123')
            ->expectsQuestion('请再次输入密码以确认', 'DifferentPass456')
            ->expectsOutput('两次输入的密码不一致，请重新操作。')
            ->assertExitCode(1);
    }
}
