<?php

namespace Tests\Unit;

use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\AuditLogger;
use PHPUnit\Framework\TestCase;

class AuditLoggerTest extends TestCase
{
    /**
     * 测试敏感字段自动递归脱敏掩码
     */
    public function test_sensitive_data_is_masked(): void
    {
        $logger = new AuditLogger();

        $rawData = [
            'username' => 'testuser',
            'email' => 'user@example.com',
            'password' => 'secret123456',
            'Password_Confirmation' => 'secret123456',
            'token' => 'bearer-token-abc',
            'secret' => 'top-secret-key',
            'nested' => [
                'api_key' => 'key-12345',
                'description' => 'safe info',
                'deep' => [
                    'credit_card' => '1234-5678-9012-3456',
                    'note' => 'all good',
                ],
            ],
        ];

        $masked = $logger->maskSensitiveData($rawData);

        $this->assertSame('testuser', $masked['username']);
        $this->assertSame('user@example.com', $masked['email']);
        $this->assertSame('******', $masked['password']);
        $this->assertSame('******', $masked['Password_Confirmation']);
        $this->assertSame('******', $masked['token']);
        $this->assertSame('******', $masked['secret']);
        $this->assertSame('******', $masked['nested']['api_key']);
        $this->assertSame('safe info', $masked['nested']['description']);
        $this->assertSame('******', $masked['nested']['deep']['credit_card']);
        $this->assertSame('all good', $masked['nested']['deep']['note']);
    }
}
