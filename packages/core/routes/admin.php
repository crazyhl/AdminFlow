<?php

use AdminFlow\Core\Http\Controllers\AuthController;
use AdminFlow\Core\Http\Controllers\UserController;
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

            // 成员与用户管理
            Route::prefix('users')->name('admin.users.')->group(function (): void {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::put('/{user}', [UserController::class, 'update'])->name('update');
                Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
                Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            });

            // 布局方案选型对比预览 (供用户直观审阅方案 A 与方案 B)
            Route::prefix('preview')->name('admin.preview.')->group(function (): void {
                Route::get('layout-a', function () {
                    $users = \AdminFlow\Core\Models\User::query()->latest('id')->paginate(15);
                    $roles = \AdminFlow\Core\Enums\Role::cases();

                    return view('adminflow-core::preview.layout_a', compact('users', 'roles'));
                })->name('layout-a');

                Route::get('layout-b', function () {
                    $users = \AdminFlow\Core\Models\User::query()->latest('id')->paginate(15);
                    $roles = \AdminFlow\Core\Enums\Role::cases();

                    return view('adminflow-core::preview.layout_b', compact('users', 'roles'));
                })->name('layout-b');
            });
        });
    });
