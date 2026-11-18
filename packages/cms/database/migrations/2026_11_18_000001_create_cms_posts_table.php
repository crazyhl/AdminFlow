<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_posts', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('adminflow_users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('body_markdown');
            $table->longText('body_html')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('type', 20)->default('post')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('allow_comments')->default(true);
            $table->unsignedBigInteger('lock_version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_posts');
    }
};
