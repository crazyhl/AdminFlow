<?php

namespace AdminFlow\Core\Models;

use AdminFlow\Core\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * 指定核心用户表名
     */
    protected $table = 'adminflow_users';

    /**
     * 可批量赋值的属性
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
        'role',
    ];

    /**
     * 序列化时隐藏的敏感属性
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            // @mago-expect lint:no-literal-password
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * 关联的操作审计日志
     */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * 检查用户是否为系统超级管理员
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin === true || $this->role === Role::SUPER_ADMIN;
    }

    /**
     * 检查账号是否处于启用状态
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * 检查用户是否具备指定权限标识
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role instanceof Role && $this->role->hasPermission($permission);
    }
}
