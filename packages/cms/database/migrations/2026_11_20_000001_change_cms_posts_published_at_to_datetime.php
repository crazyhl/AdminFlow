<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_posts', static function (Blueprint $table): void {
            $table->dateTime('published_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $outOfBoundsCount = DB::table('cms_posts')
            ->whereNotNull('published_at')
            ->where(static function ($query): void {
                $query->where('published_at', '>', '2038-01-19 03:14:07')->orWhere(
                    'published_at',
                    '<',
                    '1970-01-01 00:00:01',
                );
            })
            ->count();

        if ($outOfBoundsCount > 0) {
            throw new RuntimeException(
                "回滚中止：当前 cms_posts 表包含 {$outOfBoundsCount} 条超出 MySQL TIMESTAMP 限制（1970-2038）的发布记录，强制降级将导致数据损坏或 1292 异常。请先清理或归档超界数据后再执行回滚。",
            );
        }

        Schema::table('cms_posts', static function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable()->change();
        });
    }
};
