<?php

namespace AdminFlow\Core\Providers;

use AdminFlow\Core\Commands\AdminInitCommand;
use AdminFlow\Core\Services\AuditLogger;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any core application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, function () {
            return new AuditLogger();
        });
    }

    /**
     * Bootstrap any core services, views, migrations, and routes.
     */
    public function boot(): void
    {
        // 自动加载核心数据表迁移
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 自动加载核心双语翻译包
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'adminflow-core');

        // 控制台命令注册
        if ($this->app->runningInConsole()) {
            $this->commands([
                AdminInitCommand::class,
            ]);
        }
    }
}
