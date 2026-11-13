<?php

use AdminFlow\Core\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['web'])
    ->group(function (): void {
        // 访客认证路由
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('login', [AuthController::class, 'login'])->name('admin.login.submit');
        Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

        // 受保护的后台路由
        Route::middleware(['admin.auth'])->group(function (): void {
            Route::get('/', function () {
                $auditLogs = \AdminFlow\Core\Models\AuditLog::query()->with('user')->latest('id')->take(5)->get();

                return view('adminflow-core::dashboard', compact('auditLogs'));
            })->name('admin.dashboard');
        });
    });
