<?php

namespace AdminFlow\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * 表名
     */
    protected $table = 'adminflow_audit_logs';

    /**
     * 审计日志为不可变记录，禁用 updated_at
     */
    public const UPDATED_AT = null;

    /**
     * 可批量赋值属性
     *
     * @var list<string>
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
