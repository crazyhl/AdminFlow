<?php

namespace AdminFlow\Core\Services;

use AdminFlow\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * 需自动脱敏的敏感字段键名（不区分大小写）
     *
     * @var list<string>
     */
    protected array $maskedKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'authorization',
        'credit_card',
        'card_number',
        'cvv',
        'remember_token',
    ];

    /**
     * 记录一条审计日志
     *
     * @param string $action 操作动词（如 'auth.login', 'auth.admin_init', 'user.update'）
     * @param Model|null $target 操作的目标实体
     * @param array<string, mixed> $details 操作携带的业务明细（自动脱敏）
     * @param int|null $userId 操作人 ID（未指定则自动从当前会话提取）
     * @param string|null $ip 客户端 IP
     * @param string|null $userAgent 客户端 UA
     */
    public function log(
        string $action,
        ?Model $target = null,
        array $details = [],
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): AuditLog {
        $resolvedUserId = $userId ?? Auth::id();

        if ($ip === null) {
            $ip = App::runningInConsole() ? '127.0.0.1' : request()?->ip();
        }

        if ($userAgent === null) {
            $userAgent = App::runningInConsole() ? 'CLI' : request()?->userAgent();
        }

        $targetType = $target ? $target->getMorphClass() : null;
        $targetId = $target ? (string) $target->getKey() : null;

        return AuditLog::create([
            'user_id' => $resolvedUserId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $ip ? substr($ip, 0, 45) : null,
            'user_agent' => $userAgent,
            'details' => $this->maskSensitiveData($details),
            'created_at' => now(),
        ]);
    }

    /**
     * 便捷静态代理调用
     *
     * @param string $action
     * @param Model|null $target
     * @param array<string, mixed> $details
     * @param int|null $userId
     * @param string|null $ip
     * @param string|null $userAgent
     */
    public static function record(
        string $action,
        ?Model $target = null,
        array $details = [],
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): AuditLog {
        /** @var self $instance */
        $instance = App::make(self::class);

        return $instance->log($action, $target, $details, $userId, $ip, $userAgent);
    }

    /**
     * 递归对敏感字段进行脱敏遮罩
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function maskSensitiveData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = is_string($key) ? strtolower($key) : '';

            if (in_array($lowerKey, $this->maskedKeys, true)) {
                $sanitized[$key] = '******';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->maskSensitiveData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
