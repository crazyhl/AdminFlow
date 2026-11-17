<?php

namespace AdminFlow\Core\Services;

use AdminFlow\Core\Enums\Role;

class PermissionRegistry
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $rolePermissions = [];

    /**
     * 为指定角色注册扩展权限
     *
     * @param Role|string $role
     * @param array<int, string> $permissions
     */
    public function registerRolePermissions(Role|string $role, array $permissions): void
    {
        $roleKey = $role instanceof Role ? $role->value : $role;
        $existing = $this->rolePermissions[$roleKey] ?? [];
        $this->rolePermissions[$roleKey] = array_values(array_unique(array_merge($existing, $permissions)));
    }

    /**
     * 获取角色的所有有效权限列表
     *
     * @return array<int, string>
     */
    public function getPermissions(Role|string $role): array
    {
        $roleKey = $role instanceof Role ? $role->value : $role;

        return $this->rolePermissions[$roleKey] ?? [];
    }
}
