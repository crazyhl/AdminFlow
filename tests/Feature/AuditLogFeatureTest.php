<?php

namespace Tests\Feature;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试 AuditLogger::record 正常持久化及脱敏
     */
    public function test_record_audit_log_successfully(): void
    {
        $user = User::create([
            'name' => '审计测试员',
            'email' => 'auditor@example.com',
            'password' => 'password123',
            'is_admin' => true,
            'role' => Role::SUPER_ADMIN,
        ]);

        $log = AuditLogger::record(
            action: 'user.update',
            target: $user,
            details: [
                'action_type' => 'status_change',
                'password' => 'new_plain_password',
                'meta' => [
                    'token' => 'sensitive-token',
                    'reason' => '定期维护',
                ],
            ],
            userId: $user->id
        );

        $this->assertDatabaseHas('adminflow_audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'user.update',
            'target_type' => $user->getMorphClass(),
            'target_id' => (string) $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CLI',
        ]);

        $this->assertSame('******', $log->details['password']);
        $this->assertSame('******', $log->details['meta']['token']);
        $this->assertSame('定期维护', $log->details['meta']['reason']);

        // 测试关联关系
        $this->assertSame($user->id, $log->user->id);
        $this->assertTrue($user->auditLogs->contains($log));
    }

    /**
     * 测试 adminflow:admin-init 成功后自动记录审计日志
     */
    public function test_admin_init_creates_audit_log(): void
    {
        $this->artisan('adminflow:admin-init', ['--locale' => 'zh_CN'])
            ->expectsQuestion('请输入管理员姓名/昵称', '系统超管')
            ->expectsQuestion('请输入管理员邮箱地址', 'super@example.com')
            ->expectsQuestion('请输入管理员密码（至少 8 位，包含字母与数字）', 'Secr3tP@ss2026')
            ->expectsQuestion('请再次输入密码以确认', 'Secr3tP@ss2026')
            ->assertExitCode(0);

        $admin = User::where('email', 'super@example.com')->first();
        $this->assertNotNull($admin);

        $log = AuditLog::where('action', 'auth.admin_init')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('super@example.com', $log->details['email']);
        $this->assertSame('系统超管', $log->details['name']);
        $this->assertSame('zh_CN', $log->details['locale']);
        // 确保没有把明文密码泄露在审计日志明细中
        $this->assertArrayNotHasKey('password', $log->details);
    }
}
