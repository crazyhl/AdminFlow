<?php

namespace AdminFlow\CMS\Services;

use AdminFlow\Core\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class PostAuthorSafety
{
    /**
     * 在删除用户前主动解除名下 CMS 文章与页面的关联，防止未跑升级迁移旧库发生外键级联误删
     */
    public static function safeDissociate(User $target, ?User $actor = null): void
    {
        $hasPosts = DB::table('cms_posts')->where('user_id', $target->id)->exists();
        if (!$hasPosts) {
            return;
        }

        try {
            DB::table('cms_posts')->where('user_id', $target->id)->update(['user_id' => null]);

            return;
        } catch (Throwable $e) {
            // 列可能为 NOT NULL（旧库结构未升级），降级转移给备用管理员
            unset($e);
        }

        $fallbackUserId = $actor !== null
            ? $actor->id
            : DB::table('adminflow_users')->where('id', '!=', $target->id)->where('is_admin', true)->value('id');

        if ($fallbackUserId !== null) {
            try {
                DB::table('cms_posts')->where('user_id', $target->id)->update(['user_id' => $fallbackUserId]);

                return;
            } catch (Throwable $e) {
                // 忽略重试异常，统一在后文抛出
                unset($e);
            }
        }

        throw new InvalidArgumentException(
            '该成员名下仍有关联文章或独立页面，解绑作者失败，已中止删除以防止级联误删内容',
        );
    }
}
