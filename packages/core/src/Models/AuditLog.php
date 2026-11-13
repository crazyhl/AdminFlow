<?php

namespace AdminFlow\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 操作审计日志模型
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $target_type
 * @property string|null $target_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $details
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'adminflow_audit_logs';

    /**
     * 审计日志为不可变记录，禁用 updated_at
     */
    public const UPDATED_AT = null;

    /**
     * 可批量赋值属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    /**
     * 属性类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * 关联的操作人
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
