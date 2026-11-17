<?php

namespace AdminFlow\Core\Enums;

use AdminFlow\Core\Services\PermissionRegistry;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * 获取角色的中文显示名称
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => '超级管理员',
            self::EDITOR => '内容编辑员',
            self::VIEWER => '只读访客',
        };
    }

    /**
     * 获取角色默认具备的权限标识列表（代码定义）
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        $base = match ($this) {
            self::SUPER_ADMIN => [
                '*', // 超级管理员拥有所有权限
            ],
            self::EDITOR => [
                'core.dashboard.view',
            ],
            self::VIEWER => [
                'core.dashboard.view',
            ],
        };

        if (in_array('*', $base, true)) {
            return $base;
        }

        if (function_exists('app') && app()->bound(PermissionRegistry::class)) {
            /** @var PermissionRegistry $registry */
            $registry = app(PermissionRegistry::class);
            $extra = $registry->getPermissions($this);

            return array_values(array_unique(array_merge($base, $extra)));
        }

        return $base;
    }

    /**
     * 检查当前角色是否拥有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions();

        if (in_array('*', $perms, true)) {
            return true;
        }

        return in_array($permission, $perms, true);
    }
}
