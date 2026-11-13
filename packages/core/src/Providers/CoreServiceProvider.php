<?php

namespace AdminFlow\Core\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core application services.
     */
    public function register(): void
    {
        // 核心服务注册
    }

    /**
     * Bootstrap any core services, views, migrations, and routes.
     */
    public function boot(): void
    {
        // 自动加载核心数据表迁移
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
