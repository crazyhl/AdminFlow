<?php

use AdminFlow\CMS\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/cms')
    ->middleware(['web', 'admin.auth'])
    ->name('admin.cms.')
    ->group(function (): void {
        // 文章与独立页面管理
        Route::prefix('posts')
            ->name('posts.')
            ->group(function (): void {
                Route::get('/', [PostController::class, 'index'])->name('index');
                Route::get('/create', [PostController::class, 'create'])->name('create');
                Route::post('/', [PostController::class, 'store'])->name('store');
                Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
                Route::put('/{post}', [PostController::class, 'update'])->name('update');
                Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
                Route::post('/{post}/toggle-publish', [PostController::class, 'togglePublish'])->name('toggle-publish');
                Route::get('/{post}/preview', [PostController::class, 'preview'])->name('preview');
                Route::post('/preview-render', [PostController::class, 'previewRender'])->name('preview-render');
            });
    });
