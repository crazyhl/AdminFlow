<?php

namespace AdminFlow\Core\Policies;

use AdminFlow\Core\Models\User;

class UserPolicy
{
    /**
     * 超级管理员放行检查
     */
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->isSuperAdmin()) {
            // 超管在针对自身的状态、删除或自改角色操作上仍需进入具体策略进行防御性拦截
            if (in_array($ability, ['toggleStatus', 'delete', 'assignRole'], true)) {
                return null;
            }

            return true;
        }

        return null;
    }

    /**
     * 查看用户列表权限
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('core.users.view');
    }

    /**
     * 查看具体用户信息权限
     */
    public function view(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->hasPermission('core.users.view');
    }

    /**
     * 创建用户权限
     */
    public function create(User $actor): bool
    {
        return $actor->hasPermission('core.users.manage');
    }

    /**
     * 编辑用户权限
     */
    public function update(User $actor, User $target): bool
    {
        // 非超管不可编辑超管账号
        if ($target->isSuperAdmin() && !$actor->isSuperAdmin()) {
            return false;
        }

        return $actor->id === $target->id || $actor->hasPermission('core.users.manage');
    }

    /**
     * 切换用户启用/禁用状态权限
     */
    public function toggleStatus(User $actor, User $target): bool
    {
        // 防御 1：绝不允许操作者禁用自身
        if ($actor->id === $target->id) {
            return false;
        }

        // 防御 2：绝不允许禁用超级管理员
        if ($target->isSuperAdmin()) {
            return false;
        }

        return $actor->hasPermission('core.users.manage');
    }

    /**
     * 删除用户权限
     */
    public function delete(User $actor, User $target): bool
    {
        // 防御 1：绝不允许删除自己
        if ($actor->id === $target->id) {
            return false;
        }

        // 防御 2：绝不允许删除超级管理员
        if ($target->isSuperAdmin()) {
            return false;
        }

        return $actor->hasPermission('core.users.manage');
    }

    /**
     * 分配/变更用户角色权限（需要具备用户管理权限，且禁止变更操作者自身角色以防越权与误降级）
     */
    public function assignRole(User $actor, User $target): bool
    {
        // 防御：绝不允许变更自己的角色
        if ($actor->id === $target->id) {
            return false;
        }

        return $actor->hasPermission('core.users.manage');
    }
}
