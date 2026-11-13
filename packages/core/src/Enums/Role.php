<?php

namespace AdminFlow\Core\Enums;

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
        return match ($this) {
            self::SUPER_ADMIN => [
                '*', // 超级管理员拥有所有权限
            ],
            self::EDITOR => [
                'cms.posts.view',
                'cms.posts.create',
                'cms.posts.edit',
                'cms.posts.publish',
                'cms.media.upload',
                'cms.comments.moderate',
            ],
            self::VIEWER => [
                'core.dashboard.view',
                'cms.posts.view',
            ],
        };
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
