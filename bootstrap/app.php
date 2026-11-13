<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__ . '/../routes/web.php', commands: __DIR__ . '/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        // 全局及 Web 中间件定义（CSRF, Session, Cookie 等）
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 统一异常与 409 / 422 反馈约定
    })
    ->create();
