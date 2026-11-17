<?php

namespace AdminFlow\Core\Providers;

use AdminFlow\Core\Commands\AdminInitCommand;
use AdminFlow\Core\Http\Middleware\AuthenticateAdmin;
use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Policies\AuditLogPolicy;
use AdminFlow\Core\Policies\UserPolicy;
use AdminFlow\Core\Services\AuditLogger;
use AdminFlow\Core\Services\PermissionRegistry;
use AdminFlow\Core\Services\UserService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class, static fn() => new PermissionRegistry());

        $this->app->singleton(AuditLogger::class, static fn() => new AuditLogger());

        $this->app->singleton(UserService::class, static fn() => new UserService());

        // 注册独立 Admin Guard 与 Provider
        config([
            'auth.guards.admin' => [
                'driver' => 'session',
                'provider' => 'admin_users',
            ],
            'auth.providers.admin_users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ],
        ]);
    }

    /**
     * Bootstrap any core services, views, migrations, and routes.
     */
    public function boot(Router $router): void
    {
        // 注册授权策略
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        // 注册后台通用 Blade 组件命名空间 (使用 <x-admin::组件名>)
        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'admin');

        // 注册中间件别名
        $router->aliasMiddleware('admin.auth', AuthenticateAdmin::class);

        // 自动加载核心数据表迁移
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 自动加载核心双语翻译包
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'adminflow-core');

        // 自动加载核心视图
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'adminflow-core');

        // 侧边栏公共视图合成器 (解耦 Blade 视图对 Model 的直接依赖)
        View::composer('adminflow-core::layouts.partials.sidebar', static function (\Illuminate\View\View $view): void {
            $view->with('sidebarUserCount', User::query()->count());
        });

        // 加载后台路由
        $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');

        // 控制台命令注册
        if ($this->app->runningInConsole()) {
            $this->commands([
                AdminInitCommand::class,
            ]);
        }
    }
}
