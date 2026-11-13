<?php

namespace AdminFlow\Core\Providers;

use AdminFlow\Core\Commands\AdminInitCommand;
use AdminFlow\Core\Http\Middleware\AuthenticateAdmin;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Policies\UserPolicy;
use AdminFlow\Core\Services\AuditLogger;
use AdminFlow\Core\Services\UserService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core application services.
     */
    public function register(): void
    {
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

        // 注册中间件别名
        $router->aliasMiddleware('admin.auth', AuthenticateAdmin::class);

        // 自动加载核心数据表迁移
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 自动加载核心双语翻译包
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'adminflow-core');

        // 自动加载核心视图
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'adminflow-core');

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
