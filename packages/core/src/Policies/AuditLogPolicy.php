<?php

namespace AdminFlow\Core\Policies;

use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;

class AuditLogPolicy
{
    /**
     * 超级管理员全部放行
     */
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * 查看审计日志列表权限
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('core.audit.view') || $actor->hasPermission('core.users.manage');
    }

    /**
     * 查看具体审计详情权限
     */
    public function view(User $actor, AuditLog $auditLog): bool
    {
        return $this->viewAny($actor);
    }
}
