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
        Schema::table('cms_posts', static function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('adminflow_users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚恢复 NOT NULL 之前，必须先修复所有 user_id 为 NULL 的文章；若无账号接收，中止回滚并保留内容
        $hasNullPosts = DB::table('cms_posts')->whereNull('user_id')->exists();

        if ($hasNullPosts) {
            $fallbackUserId = DB::table('adminflow_users')
                ->where('is_admin', true)
                ->orWhere('role', 'super_admin')
                ->value('id') ?? DB::table('adminflow_users')->value('id');

            if ($fallbackUserId === null) {
                throw new \RuntimeException(
                    '存在无作者（user_id 为 NULL）的文章或独立页面，且未找到可接收内容的账号，中止回滚以保护既有内容不被删除。',
                );
            }

            DB::table('cms_posts')->whereNull('user_id')->update(['user_id' => $fallbackUserId]);
        }

        Schema::table('cms_posts', static function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('adminflow_users')->cascadeOnDelete();
        });
    }
};
