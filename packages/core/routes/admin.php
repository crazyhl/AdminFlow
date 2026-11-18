<?php

use AdminFlow\Core\Http\Controllers\AuditLogController;
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
            Route::get('/', function (\AdminFlow\Core\Services\UserService $userService) {
                $canViewAudit = \Illuminate\Support\Facades\Gate::allows(
                    'viewAny',
                    \AdminFlow\Core\Models\AuditLog::class,
                );

                $auditLogs = $canViewAudit
                    ? \AdminFlow\Core\Models\AuditLog::query()->with('user')->latest('id')->take(8)->get()
                    : collect();

                $userStats = $userService->getStatistics();

                $stats = [
                    'users_count' => $userStats['total'],
                    'active_users_count' => $userStats['active'],
                    'audit_count' => $canViewAudit ? \AdminFlow\Core\Models\AuditLog::query()->count() : 0,
                ];

                return view('adminflow-core::dashboard', compact('auditLogs', 'stats'));
            })->name('admin.dashboard');

            // 成员与用户管理
            Route::prefix('users')
                ->name('admin.users.')
                ->group(function (): void {
                    Route::get('/', [UserController::class, 'index'])->name('index');
                    Route::post('/', [UserController::class, 'store'])->name('store');
                    Route::put('/{user}', [UserController::class, 'update'])->name('update');
                    Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name(
                        'toggle-active',
                    );
                    Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name(
                        'reset-password',
                    );
                    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
                });

            // 操作审计日志管理 (中文化全量流水与筛选)
            Route::prefix('audit-logs')
                ->name('admin.audit-logs.')
                ->group(function (): void {
                    Route::get('/', [AuditLogController::class, 'index'])->name('index');
                });

            // 布局方案选型对比预览 (仅在本地开发与测试环境中供方案评审)
            if (app()->environment('local', 'testing')) {
                Route::prefix('preview')
                    ->name('admin.preview.')
                    ->group(function (): void {
                        Route::get('layout-a', function (AdminFlow\Core\Services\UserService $userService) {
                            \Illuminate\Support\Facades\Gate::authorize('viewAny', \AdminFlow\Core\Models\User::class);

                            $users = \AdminFlow\Core\Models\User::query()->latest('id')->paginate(15);
                            $roles = \AdminFlow\Core\Enums\Role::cases();
                            $stats = $userService->getStatistics();

                            return view('adminflow-core::preview.layout_a', compact('users', 'roles', 'stats'));
                        })->name('layout-a');

                        Route::get('layout-b', function (AdminFlow\Core\Services\UserService $userService) {
                            \Illuminate\Support\Facades\Gate::authorize('viewAny', \AdminFlow\Core\Models\User::class);

                            $users = \AdminFlow\Core\Models\User::query()->latest('id')->paginate(15);
                            $roles = \AdminFlow\Core\Enums\Role::cases();
                            $stats = $userService->getStatistics();

                            return view('adminflow-core::preview.layout_b', compact('users', 'roles', 'stats'));
                        })->name('layout-b');
                    });
            }
        });
    });
