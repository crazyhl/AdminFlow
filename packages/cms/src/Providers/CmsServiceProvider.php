<?php

namespace AdminFlow\CMS\Providers;

use AdminFlow\CMS\Models\Post;
use AdminFlow\CMS\Policies\PostPolicy;
use AdminFlow\CMS\Services\MarkdownService;
use AdminFlow\CMS\Services\PostAuthorSafety;
use AdminFlow\CMS\Services\PostDataResolver;
use AdminFlow\CMS\Services\PostService;
use AdminFlow\CMS\Services\PostSlugService;
use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Services\NavigationRegistry;
use AdminFlow\Core\Services\PermissionRegistry;
use AdminFlow\Core\Services\UserService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * 注册 CMS 服务
     */
    public function register(): void
    {
        $this->app->singleton(MarkdownService::class, static fn() => new MarkdownService());
        $this->app->singleton(PostDataResolver::class, static fn() => new PostDataResolver());
        $this->app->singleton(PostSlugService::class, static fn() => new PostSlugService());
        $this->app->singleton(
            PostService::class,
            static fn($app) => new PostService(
                $app->make(MarkdownService::class),
                $app->make(PostDataResolver::class),
                $app->make(PostSlugService::class),
            ),
        );
    }

    /**
     * 引导 CMS 路由、视图、迁移与权限
     */
    public function boot(Router $router): void
    {
        // 注册授权策略
        Gate::policy(Post::class, PostPolicy::class);

        // 注册插件权限
        if ($this->app->bound(PermissionRegistry::class)) {
            /** @var PermissionRegistry $registry */
            $registry = $this->app->make(PermissionRegistry::class);

            $registry->registerRolePermissions(Role::EDITOR, [
                'cms.posts.view',
                'cms.posts.create',
                'cms.posts.edit',
                'cms.posts.publish',
                'cms.posts.delete',
                'cms.media.upload',
                'cms.comments.moderate',
            ]);

            $registry->registerRolePermissions(Role::VIEWER, [
                'cms.posts.view',
            ]);
        }

        // 注册业务插件删除前扩展点：防止未执行升级迁移的历史库在删除用户时发生外键级联误删
        if ($this->app->bound(UserService::class)) {
            $this->app->make(UserService::class)->beforeDelete([PostAuthorSafety::class, 'safeDissociate']);
        }

        // 注册 CMS 侧边栏菜单与所需权限
        if ($this->app->bound(NavigationRegistry::class)) {
            /** @var NavigationRegistry $navRegistry */
            $navRegistry = $this->app->make(NavigationRegistry::class);
            $navRegistry->registerSection('cms', '创作工坊 (CMS 插件)', 30);
            $navRegistry->registerItem('cms', [
                'title' => 'Markdown 文章写作',
                'route' => 'admin.cms.posts.index',
                'active' => 'admin.cms.posts.*',
                'icon' => 'bi-pen',
                'permission' => 'cms.posts.view',
            ]);
        }

        // 自动加载 CMS 数据表迁移
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 自动加载 CMS 视图
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'adminflow-cms');

        // 加载 CMS 后台路由
        if (file_exists(__DIR__ . '/../../routes/admin.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');
        }
    }
}
