<?php

namespace AdminFlow\CMS\Policies;

use AdminFlow\CMS\Models\Post;
use AdminFlow\Core\Models\User;

class PostPolicy
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
     * 查看内容列表
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('cms.posts.view');
    }

    /**
     * 查看单篇内容详情或受保护预览
     */
    public function view(User $actor, Post $post): bool
    {
        return $actor->hasPermission('cms.posts.view');
    }

    /**
     * 创建内容
     */
    public function create(User $actor): bool
    {
        return $actor->hasPermission('cms.posts.create');
    }

    /**
     * 编辑内容
     */
    public function update(User $actor, Post $post): bool
    {
        return $actor->hasPermission('cms.posts.edit');
    }

    /**
     * 发布或撤回内容
     */
    public function publish(User $actor, ?Post $post = null): bool
    {
        return $actor->hasPermission('cms.posts.publish');
    }

    /**
     * 删除内容
     */
    public function delete(User $actor, Post $post): bool
    {
        return $actor->hasPermission('cms.posts.delete');
    }
}
