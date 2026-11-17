<?php

namespace AdminFlow\CMS\Providers;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Services\PermissionRegistry;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Register CMS plugin services.
     */
    public function register(): void
    {
        // CMS 插件服务注册
    }

    /**
     * Bootstrap CMS routes, views, migrations, and assets.
     */
    public function boot(): void
    {
        if ($this->app->bound(PermissionRegistry::class)) {
            /** @var PermissionRegistry $registry */
            $registry = $this->app->make(PermissionRegistry::class);

            $registry->registerRolePermissions(Role::EDITOR, [
                'cms.posts.view',
                'cms.posts.create',
                'cms.posts.edit',
                'cms.posts.publish',
                'cms.media.upload',
                'cms.comments.moderate',
            ]);

            $registry->registerRolePermissions(Role::VIEWER, [
                'cms.posts.view',
            ]);
        }
    }
}
