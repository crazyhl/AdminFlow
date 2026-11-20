<?php

namespace Tests\Feature;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use AdminFlow\CMS\Models\Post;
use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $editor;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => '超级管理员',
            'email' => 'admin@adminflow.test',
            'password' => 'AdminPass2026',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->editor = User::create([
            'name' => '内容编辑员',
            'email' => 'editor@adminflow.test',
            'password' => 'EditorPass2026',
            'role' => Role::EDITOR,
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->viewer = User::create([
            'name' => '普通访客',
            'email' => 'viewer@adminflow.test',
            'password' => 'ViewerPass2026',
            'role' => Role::VIEWER,
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.cms.posts.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_viewer_can_view_post_list_but_cannot_create(): void
    {
        // 访客有 cms.posts.view 权限，可查看列表
        $response = $this->actingAs($this->viewer, 'admin')->get(route('admin.cms.posts.index'));
        $response->assertOk();
        $response->assertSee('内容写作与管理');

        // 尝试新建，无权限返回 403
        $createResponse = $this->actingAs($this->viewer, 'admin')->get(route('admin.cms.posts.create'));
        $createResponse->assertForbidden();
    }

    public function test_htmx_search_and_filter_returns_table_partial(): void
    {
        Post::create([
            'title' => 'Laravel 深入浅出',
            'slug' => 'laravel-in-depth',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'user_id' => $this->editor->id,
            'published_at' => now(),
            'lock_version' => 1,
        ]);

        $response = $this->actingAs($this->editor, 'admin')->get(
            route('admin.cms.posts.index', ['search' => 'Laravel']),
            ['HX-Request' => 'true'],
        );

        $response->assertOk();
        $response->assertSee('posts-table-card');
        $response->assertSee('Laravel 深入浅出');
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_editor_can_create_post_with_markdown_and_xss_is_sanitized(): void
    {
        $postData = [
            'title' => '第一篇测试文章',
            'slug' => 'first-test-post',
            'summary' => '这是测试摘要',
            'body_markdown' => "# 标题一\n\n正文内容。<script>alert('xss')</script>\n\n[安全链接](https://example.com) [危险链接](javascript:alert(1))",
            'type' => 'post',
            'status' => 'draft',
            'is_pinned' => '1',
            'allow_comments' => '1',
        ];

        $response = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), $postData);

        $response->assertRedirect();
        $this->assertDatabaseHas('cms_posts', [
            'title' => '第一篇测试文章',
            'slug' => 'first-test-post',
            'user_id' => $this->editor->id,
            'lock_version' => 1,
        ]);

        $post = Post::query()->where('slug', 'first-test-post')->first();
        $this->assertNotNull($post);

        // 验证 XSS 脚本被彻底剥离与净化
        $this->assertStringNotContainsString("<script>alert('xss')</script>", $post->body_html);
        $this->assertStringNotContainsString('javascript:alert(1)', $post->body_html);
        $this->assertStringContainsString('标题一', $post->body_html);

        // 验证审计日志记录
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'cms.posts.create',
            'user_id' => $this->editor->id,
        ]);
    }

    public function test_editor_can_update_post_and_version_increments(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '原始标题',
            'slug' => 'original-slug',
            'body_markdown' => '原始 Markdown',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        $updateData = [
            'title' => '修改后的标题',
            'slug' => 'original-slug',
            'body_markdown' => '修改后的正文内容',
            'type' => 'post',
            'status' => 'draft',
            'lock_version' => 1, // 提交预期版本号
        ];

        $response = $this->actingAs($this->editor, 'admin')->put(route('admin.cms.posts.update', $post), $updateData);

        $response->assertRedirect();

        $post->refresh();
        $this->assertSame('修改后的标题', $post->title);
        $this->assertSame(2, $post->lock_version); // 版本号自增至 2
    }

    public function test_optimistic_concurrency_lock_throws_409_when_version_mismatch(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '并发测试文章',
            'slug' => 'concurrency-post',
            'body_markdown' => '版本 1 内容',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 3, // 当前数据库版本为 3
        ]);

        // 模拟用户 A 持有旧版本 2 提交修改
        $conflictData = [
            'title' => '用户 A 提交的修改',
            'slug' => 'concurrency-post',
            'body_markdown' => '过期的修改内容',
            'type' => 'post',
            'status' => 'draft',
            'lock_version' => 2, // 预期版本 2 不等于 数据库版本 3
        ];

        $response = $this->actingAs($this->editor, 'admin')->put(route('admin.cms.posts.update', $post), $conflictData);

        // 验证返回 409 Conflict 状态码
        $response->assertConflict();

        // 验证数据库内容未被篡改
        $post->refresh();
        $this->assertSame('并发测试文章', $post->title);
        $this->assertSame(3, $post->lock_version);
    }

    public function test_htmx_update_returns_200_and_triggers_without_page_reload(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => 'HTMX 原标题',
            'slug' => 'htmx-slug',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        $updateData = [
            'title' => 'HTMX 保存后的标题',
            'slug' => 'htmx-slug',
            'body_markdown' => '修改后的正文',
            'type' => 'post',
            'status' => 'draft',
            'lock_version' => 1,
        ];

        $response = $this->actingAs($this->editor, 'admin')->withHeader('HX-Request', 'true')->put(
            route('admin.cms.posts.update', $post),
            $updateData,
        );

        $response->assertOk();
        $response->assertHeader('HX-Trigger');
        $response->assertSessionMissing('success');

        $trigger = json_decode((string) $response->headers->get('HX-Trigger'), true);
        $this->assertArrayHasKey('postSaved', $trigger);
        $this->assertSame(2, $trigger['postSaved']['version']);
        $this->assertArrayHasKey('showToast', $trigger);

        $post->refresh();
        $this->assertSame(2, $post->lock_version);
        $this->assertSame('HTMX 保存后的标题', $post->title);
    }

    public function test_htmx_concurrency_conflict_returns_409_partial_view_and_error_trigger(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '并发 HTMX 文章',
            'slug' => 'concurrency-htmx',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 2,
        ]);

        $conflictData = [
            'title' => '提交冲突标题',
            'slug' => 'concurrency-htmx',
            'body_markdown' => '正文',
            'type' => 'post',
            'status' => 'draft',
            'lock_version' => 1, // 提交旧版本 1
        ];

        $response = $this->actingAs($this->editor, 'admin')->withHeader('HX-Request', 'true')->put(
            route('admin.cms.posts.update', $post),
            $conflictData,
        );

        $response->assertConflict();
        $response->assertSee('内容已被他人修改');
        $response->assertSee('重新加载最新数据');
        $response->assertHeader('HX-Trigger');

        $trigger = json_decode((string) $response->headers->get('HX-Trigger'), true);
        $this->assertSame('error', $trigger['showToast']['type']);
    }

    public function test_htmx_validation_failure_returns_422_partial_view(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '校验测试文章',
            'slug' => 'validation-post',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        // 提交空标题
        $invalidData = [
            'title' => '',
            'body_markdown' => '正文',
            'type' => 'post',
            'status' => 'draft',
            'lock_version' => 1,
        ];

        $response = $this->actingAs($this->editor, 'admin')->withHeader('HX-Request', 'true')->put(
            route('admin.cms.posts.update', $post),
            $invalidData,
        );

        $response->assertUnprocessable();
        $response->assertSee('文章标题不能为空');
        $response->assertHeader('HX-Trigger');
    }

    public function test_editor_can_toggle_publish_status(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '待发布文章',
            'slug' => 'publish-test',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        // 触发发布
        $response = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.toggle-publish', $post), [
            'lock_version' => 1,
        ]);

        $response->assertRedirect();
        $post->refresh();
        $this->assertSame(PostStatus::PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame(2, $post->lock_version);

        // 再次触发撤回
        $unpublishResponse = $this->actingAs($this->editor, 'admin')->post(
            route('admin.cms.posts.toggle-publish', $post),
            [
                'lock_version' => 2,
            ],
        );

        $unpublishResponse->assertRedirect();
        $post->refresh();
        $this->assertSame(PostStatus::DRAFT, $post->status);
        $this->assertSame(3, $post->lock_version);
    }

    public function test_protected_preview_accessible_to_logged_in_editor_but_forbidden_to_guest(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '机密草稿文章',
            'slug' => 'secret-draft',
            'body_markdown' => '# 机密内幕\n\n草稿绝不向外暴露',
            'body_html' => '<h1>机密内幕</h1><p>草稿绝不向外暴露</p>',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        // 未登录访客重定向
        $guestResponse = $this->get(route('admin.cms.posts.preview', $post));
        $guestResponse->assertRedirect(route('admin.login'));

        // 登录编辑员可正常受保护预览
        $previewResponse = $this->actingAs($this->editor, 'admin')->get(route('admin.cms.posts.preview', $post));
        $previewResponse->assertOk();
        $previewResponse->assertSee('受保护预览模式');
        $previewResponse->assertSee('机密草稿文章');
        $previewResponse->assertSee('草稿绝不向外暴露');
    }

    public function test_editor_can_delete_post_and_records_audit_log(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '将要删除的文章',
            'slug' => 'delete-test-post',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        $response = $this->actingAs($this->editor, 'admin')->delete(route('admin.cms.posts.destroy', $post), [
            'lock_version' => 1,
        ]);

        $response->assertRedirect(route('admin.cms.posts.index'));
        $this->assertDatabaseMissing('cms_posts', [
            'id' => $post->id,
        ]);

        // 验证审计日志
        $this->assertDatabaseHas('adminflow_audit_logs', [
            'action' => 'cms.posts.delete',
            'user_id' => $this->editor->id,
        ]);
    }

    public function test_concurrency_conflict_when_deleting_modified_post(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '并发删除测试文章',
            'slug' => 'delete-conflict-post',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 2, // 数据库当前版本为 2
        ]);

        // 提交已过期的版本 1 进行删除
        $response = $this
            ->actingAs($this->editor, 'admin')
            ->withHeader('HX-Request', 'true')
            ->delete(route('admin.cms.posts.destroy', $post), ['lock_version' => 1]);

        $response->assertConflict();
        $response->assertHeader('HX-Reswap', 'none');
        $this->assertSame('', $response->getContent()); // 空内容不覆盖表格
        $this->assertDatabaseHas('cms_posts', [
            'id' => $post->id,
        ]);
    }

    public function test_quick_publish_conflict_returns_hx_reswap_none_and_toast(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '快捷发布并发测试',
            'slug' => 'quick-publish-conflict',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 3, // 数据库当前版本为 3
        ]);

        // 提交旧版本 2 进行快速发布
        $response = $this
            ->actingAs($this->editor, 'admin')
            ->withHeader('HX-Request', 'true')
            ->post(route('admin.cms.posts.toggle-publish', $post), ['lock_version' => 2]);

        $response->assertConflict();
        $response->assertHeader('HX-Reswap', 'none');
        $this->assertSame('', $response->getContent()); // 不返回覆盖表格的纯文本
        $response->assertHeader('HX-Trigger');

        $trigger = json_decode((string) $response->headers->get('HX-Trigger'), true);
        $this->assertSame('error', $trigger['showToast']['type']);
    }

    public function test_history_restore_request_returns_full_index_view(): void
    {
        $response = $this->actingAs($this->editor, 'admin')->get(route('admin.cms.posts.index'), [
            'HX-Request' => 'true',
            'HX-History-Restore-Request' => 'true',
        ]);

        $response->assertOk();
        // 验证返回包含整体完整布局，而非仅仅局部表格片段
        $response->assertSee('内容写作与管理');
        $response->assertSee('撰写新文章 / 页面');
    }

    public function test_deleting_user_retains_posts_with_null_user_id(): void
    {
        $author = User::create([
            'name' => '待删除作者',
            'email' => 'author_to_delete@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::EDITOR,
            'is_admin' => false,
            'is_active' => true,
        ]);

        $post = Post::create([
            'user_id' => $author->id,
            'title' => '永久留存的文章',
            'slug' => 'retained-post',
            'body_markdown' => '正文内容',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'lock_version' => 1,
        ]);

        // 删除作者
        $author->delete();

        // 验证文章并未被级联删除，user_id 自动置 null
        $post->refresh();
        $this->assertNull($post->user_id);
        $this->assertNull($post->author);
    }

    public function test_user_service_delete_protects_posts_from_cascade(): void
    {
        $userService = app(\AdminFlow\Core\Services\UserService::class);

        $author = User::create([
            'name' => '服务级删除作者',
            'email' => 'author_service_delete@adminflow.test',
            'password' => 'Pass123456',
            'role' => Role::EDITOR,
            'is_admin' => false,
            'is_active' => true,
        ]);

        $post = Post::create([
            'user_id' => $author->id,
            'title' => '安全留存文章',
            'slug' => 'safe-retained-post',
            'body_markdown' => '正文内容',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'lock_version' => 1,
        ]);

        $userService->delete($author, $this->admin);

        $this->assertDatabaseMissing('adminflow_users', ['id' => $author->id]);
        $post->refresh();
        $this->assertNull($post->user_id);
    }

    public function test_post_cover_image_url_validates_max_255_chars(): void
    {
        // 超过 255 字符拦截校验
        $tooLongUrl = 'https://example.com/images/' . str_repeat('a', 240) . '.jpg';
        $this->assertGreaterThan(255, strlen($tooLongUrl));

        $invalidData = [
            'title' => '超长封面图测试',
            'slug' => 'too-long-cover-test',
            'body_markdown' => '正文内容',
            'cover_image_url' => $tooLongUrl,
            'type' => 'post',
            'status' => 'draft',
        ];

        $failResponse = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), $invalidData);
        $failResponse->assertSessionHasErrors(['cover_image_url']);

        // 255 字符内正常保存
        $validUrl = 'https://example.com/images/' . str_repeat('a', 200) . '.jpg';
        $this->assertLessThanOrEqual(255, strlen($validUrl));

        $validData = [
            'title' => '合法封面图测试',
            'slug' => 'valid-cover-test',
            'body_markdown' => '正文内容',
            'cover_image_url' => $validUrl,
            'type' => 'post',
            'status' => 'draft',
        ];

        $successResponse = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), $validData);
        $successResponse->assertRedirect();

        $post = Post::query()->where('slug', 'valid-cover-test')->first();
        $this->assertNotNull($post);
        $this->assertSame($validUrl, $post->cover_image_url);
    }

    public function test_post_update_with_empty_published_at_preserves_published_date(): void
    {
        $publishedAt = now()->subDays(3);
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '测试发布时间保留',
            'slug' => 'test-published-at-preserve',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'published_at' => $publishedAt,
            'lock_version' => 1,
        ]);

        // 编辑已发布文章，表单传空日期
        $response = $this->actingAs($this->editor, 'admin')->put(route('admin.cms.posts.update', $post), [
            'title' => '测试发布时间保留-已修改',
            'slug' => 'test-published-at-preserve',
            'body_markdown' => '正文修改',
            'type' => 'post',
            'status' => 'published',
            'published_at' => '',
            'lock_version' => 1,
        ]);

        $response->assertRedirect();
        $post->refresh();
        $this->assertSame(PostStatus::PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertSame($publishedAt->timestamp, $post->published_at->timestamp);
    }

    public function test_two_posts_with_255_char_titles_generate_valid_unique_slugs_without_error(): void
    {
        $longTitle = str_repeat('a', 255);

        // 创建第一篇超长标题文章
        $response1 = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => $longTitle,
            'slug' => '',
            'body_markdown' => '正文1',
            'type' => 'post',
            'status' => 'draft',
        ]);
        $response1->assertRedirect();

        // 创建第二篇相同超长标题文章，自动去重 slug
        $response2 = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => $longTitle,
            'slug' => '',
            'body_markdown' => '正文2',
            'type' => 'post',
            'status' => 'draft',
        ]);
        $response2->assertRedirect();

        $posts = Post::query()->where('body_markdown', 'like', '正文%')->get();
        $this->assertCount(2, $posts);

        foreach ($posts as $item) {
            $this->assertLessThanOrEqual(255, strlen($item->slug));
        }

        $this->assertNotSame($posts[0]->slug, $posts[1]->slug);
        $this->assertStringEndsWith('-1', $posts[1]->slug);
    }

    public function test_post_statistics_aggregated_in_service_layer(): void
    {
        // 创建 2 篇已发布，1 篇草稿，1 篇独立页面
        Post::create([
            'user_id' => $this->editor->id,
            'title' => '统计文章1',
            'slug' => 'stat-post-1',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'lock_version' => 1,
        ]);
        Post::create([
            'user_id' => $this->editor->id,
            'title' => '统计文章2',
            'slug' => 'stat-post-2',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'lock_version' => 1,
        ]);
        Post::create([
            'user_id' => $this->editor->id,
            'title' => '统计文章3',
            'slug' => 'stat-post-3',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);
        Post::create([
            'user_id' => $this->editor->id,
            'title' => '统计页面4',
            'slug' => 'stat-page-4',
            'body_markdown' => '正文',
            'type' => PostType::PAGE,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        $postService = app(\AdminFlow\CMS\Services\PostService::class);
        $stats = $postService->getStatistics();

        $this->assertSame(4, $stats['total']);
        $this->assertSame(2, $stats['published']);
        $this->assertSame(2, $stats['draft']);
        $this->assertSame(1, $stats['pages']);

        // 验证前端页面正确渲染统计数字
        $response = $this->actingAs($this->editor, 'admin')->get(route('admin.cms.posts.index'));
        $response->assertOk();
        $response->assertViewHas('statistics', $stats);
    }

    public function test_duplicate_custom_slug_returns_validation_error(): void
    {
        Post::create([
            'user_id' => $this->editor->id,
            'title' => '原有自定义链接文章',
            'slug' => 'custom-fixed-slug',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        $response = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => '冲突文章',
            'slug' => 'custom-fixed-slug',
            'body_markdown' => '正文2',
            'type' => 'post',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_occupied_custom_slug_in_service_throws_validation_exception_without_silent_rename(): void
    {
        Post::create([
            'user_id' => $this->editor->id,
            'title' => '已有文章',
            'slug' => 'taken-slug',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        $service = app(\AdminFlow\CMS\Services\PostService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $service->create([
                'title' => '试图使用已有链接',
                'slug' => 'taken-slug',
                'body_markdown' => '正文',
            ], $this->editor);
        } finally {
            // 确保没有悄悄被改名为 taken-slug-1 保存入库
            $this->assertNull(Post::query()->where('slug', 'taken-slug-1')->first());
        }
    }

    public function test_post_slug_service_is_unique_violation_distinguishes_non_slug_database_errors(): void
    {
        $slugService = app(\AdminFlow\CMS\Services\PostSlugService::class);

        // 真实 Slug 唯一索引冲突异常
        $slugUniqueException = new \Illuminate\Database\QueryException(
            'mysql',
            "insert into `cms_posts` (`title`, `slug`) values ('Test', 'dup-slug')",
            [],
            new \PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'dup-slug' for key 'cms_posts.cms_posts_slug_unique'",
                23_000,
            ),
        );
        $this->assertTrue($slugService->isUniqueViolation($slugUniqueException));

        // 标题超长等字段错误（SQL 虽包含 slug 列名，但错误不是唯一键冲突）
        $dataTooLongException = new \Illuminate\Database\QueryException(
            'mysql',
            "insert into `cms_posts` (`title`, `slug`) values ('LongTitle...', 'ok-slug')",
            [],
            new \PDOException(
                "SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'title' at row 1",
                22_001,
            ),
        );
        $this->assertFalse($slugService->isUniqueViolation($dataTooLongException));

        // 其他表的唯一键冲突（如用户邮箱重复）
        $userEmailUniqueException = new \Illuminate\Database\QueryException(
            'mysql',
            "insert into `adminflow_users` (`name`, `email`) values ('Test', 'admin@example.com')",
            [],
            new \PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'admin@example.com' for key 'adminflow_users.adminflow_users_email_unique'",
                23_000,
            ),
        );
        $this->assertFalse($slugService->isUniqueViolation($userEmailUniqueException));
    }

    public function test_custom_slug_zero_is_preserved_and_not_replaced_by_title(): void
    {
        $service = app(\AdminFlow\CMS\Services\PostService::class);
        $post = $service->create([
            'title' => '标题不应该替换零',
            'slug' => '0',
            'body_markdown' => '正文',
        ], $this->editor);

        $this->assertSame('0', $post->slug);
    }

    public function test_htmx_update_returns_422_partial_when_service_throws_validation_exception(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '准备更新文章',
            'slug' => 'my-original-slug',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'lock_version' => 1,
        ]);

        // 模拟表单校验层通过后，保存层抛出并发占用 ValidationException
        $mock = $this->partialMock(\AdminFlow\CMS\Services\PostService::class);
        $mock
            ->shouldReceive('update')
            ->once()
            ->andThrow(\Illuminate\Validation\ValidationException::withMessages([
                'slug' => '该固定链接已被其他内容占用，请更换',
            ]));

        $response = $this->actingAs($this->editor, 'admin')->withHeader('HX-Request', 'true')->put(
            route('admin.cms.posts.update', $post),
            [
                'title' => '更新文章',
                'slug' => 'new-slug',
                'body_markdown' => '正文',
                'type' => 'post',
                'status' => 'draft',
                'lock_version' => 1,
            ],
        );

        $response->assertUnprocessable();
        $response->assertHeader('HX-Trigger');
        $this->assertStringContainsString('已被其他内容占用', (string) $response->getContent());
    }

    public function test_post_creation_with_future_date_2040_succeeds_without_error(): void
    {
        $response = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => '远期发布文章',
            'body_markdown' => '正文内容',
            'type' => 'post',
            'status' => 'draft',
            'published_at' => '2040-01-01 12:00:00',
        ]);

        $post = Post::query()->where('title', '远期发布文章')->firstOrFail();
        $response->assertRedirect(route('admin.cms.posts.edit', $post));
        $this->assertDatabaseHas('cms_posts', [
            'title' => '远期发布文章',
            'published_at' => '2040-01-01 12:00:00',
        ]);
    }

    public function test_viewer_sees_preview_link_instead_of_403_edit_link(): void
    {
        $post = Post::create([
            'user_id' => $this->editor->id,
            'title' => '权限隔离测试文章',
            'slug' => 'permission-test-post',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'lock_version' => 1,
        ]);

        // 1. 列表页：只读访客看到的是 preview 链接，而非 edit 链接
        $indexResponse = $this->actingAs($this->viewer, 'admin')->get(route('admin.cms.posts.index'));
        $indexResponse->assertOk();
        $indexContent = (string) $indexResponse->getContent();
        $this->assertStringContainsString(route('admin.cms.posts.preview', $post), $indexContent);
        $this->assertStringNotContainsString(route('admin.cms.posts.edit', $post), $indexContent);

        // 2. 预览页：只读访客看到的是「返回列表」，而非「返回编辑」
        $previewResponse = $this->actingAs($this->viewer, 'admin')->get(route('admin.cms.posts.preview', $post));
        $previewResponse->assertOk();
        $previewContent = (string) $previewResponse->getContent();
        $this->assertStringContainsString('返回列表', $previewContent);
        $this->assertStringNotContainsString('返回编辑', $previewContent);
    }

    public function test_invalid_filter_parameters_handling(): void
    {
        // 1. 普通 HTML 请求传入非法筛选（如 search 数组），重定向至无筛选列表并携带提示
        $htmlResponse = $this->actingAs($this->viewer, 'admin')->get(route('admin.cms.posts.index', ['search' => [
            'test',
        ]]));
        $htmlResponse->assertRedirect(route('admin.cms.posts.index'));
        $htmlResponse->assertSessionHas('error', '筛选参数不合法，已重置为默认列表');

        // 2. HTMX 异步请求传入非法筛选，返回 422 局部可见错误卡片与 Toast 触发事件
        $htmxResponse = $this
            ->actingAs($this->viewer, 'admin')
            ->withHeader('HX-Request', 'true')
            ->get(route('admin.cms.posts.index', ['search' => ['test']]));
        $htmxResponse->assertUnprocessable();
        $htmxResponse->assertHeader('HX-Trigger');
        $htmxResponse->assertHeader('HX-Push-Url', 'false');
        $this->assertStringContainsString('搜索参数格式不正确', (string) $htmxResponse->getContent());
        $this->assertSame(1, substr_count((string) $htmxResponse->getContent(), 'id="posts-table-card"'));

        // 3. 服务层直接调用非法类型与状态时防御性降级，不抛出异常
        $postService = app(\AdminFlow\CMS\Services\PostService::class);
        $paginator = $postService->paginate([
            'search' => ['array_search'],
            'status' => 'not_a_valid_status',
            'type' => 'not_a_valid_type',
        ]);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginator);
    }

    public function test_migration_rollback_aborts_when_published_at_has_out_of_bounds_date(): void
    {
        Post::query()->create([
            'user_id' => $this->editor->id,
            'title' => '远期测试文章',
            'slug' => 'future-date-rollback-test',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'published_at' => '2040-01-01 12:00:00',
            'lock_version' => 1,
        ]);

        $migration = require
            base_path(
                'packages/cms/database/migrations/2026_11_20_000001_change_cms_posts_published_at_to_datetime.php',
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('回滚中止：当前 cms_posts 表包含 1 条超出 MySQL TIMESTAMP 限制');

        $migration->down();
    }

    public function test_editor_without_publish_permission_cannot_publish_or_unpublish_via_store_and_update(): void
    {
        // 模拟权限拆分：当前编辑角色拥有创建和编辑权限，但不具备发布权限
        Gate::before(static function ($user, $ability): ?bool {
            if ($ability === 'publish') {
                return false;
            }

            return null;
        });

        // 1. 新建直接提交为已发布状态 -> 拒绝 403
        $storeResponse = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => '越权直接发布文章',
            'body_markdown' => '正文',
            'type' => 'post',
            'status' => 'published',
        ]);
        $storeResponse->assertForbidden();

        // 2. 新建提交为草稿状态 -> 允许通过
        $storeDraftResponse = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => '合法创建草稿文章',
            'body_markdown' => '正文',
            'type' => 'post',
            'status' => 'draft',
        ]);
        $storeDraftResponse->assertRedirect();
        $draftPost = Post::query()->where('title', '合法创建草稿文章')->firstOrFail();

        // 3. 编辑普通保存：将草稿改为发布状态 -> 拒绝 403
        $updatePublishResponse = $this->actingAs($this->editor, 'admin')->put(
            route('admin.cms.posts.update', $draftPost),
            [
                'title' => '非法升级为发布',
                'body_markdown' => '正文',
                'type' => 'post',
                'status' => 'published',
                'lock_version' => $draftPost->lock_version,
            ],
        );
        $updatePublishResponse->assertForbidden();

        // 4. 创建一篇初始已发布文章，测试编辑角色试图撤回 -> 拒绝 403
        $publishedPost = Post::query()->create([
            'user_id' => $this->editor->id,
            'title' => '已有发布文章',
            'slug' => 'already-published-post',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'published_at' => now(),
            'lock_version' => 1,
        ]);

        $unpublishResponse = $this->actingAs($this->editor, 'admin')->put(
            route('admin.cms.posts.update', $publishedPost),
            [
                'title' => '非法撤回为草稿',
                'body_markdown' => '正文',
                'type' => 'post',
                'status' => 'draft',
                'lock_version' => 1,
            ],
        );
        $unpublishResponse->assertForbidden();

        // 5. 定时发布文章：尝试将未来发布时间改到过去 -> 拒绝 403
        $scheduledPost = Post::query()->create([
            'user_id' => $this->editor->id,
            'title' => '未来定时文章',
            'slug' => 'future-scheduled-post',
            'body_markdown' => '正文',
            'type' => PostType::POST,
            'status' => PostStatus::PUBLISHED,
            'published_at' => now()->addMonths(2),
            'lock_version' => 1,
        ]);

        $modifyPublishDateResponse = $this->actingAs($this->editor, 'admin')->put(
            route('admin.cms.posts.update', $scheduledPost),
            [
                'title' => '未来定时文章',
                'body_markdown' => '正文',
                'type' => 'post',
                'status' => 'published',
                'published_at' => now()->subDay()->format('Y-m-d H:i'),
                'lock_version' => 1,
            ],
        );
        $modifyPublishDateResponse->assertForbidden();

        // 6. 编辑已发布文章文本但保持原发布时间 -> 允许通过
        $normalPublishedUpdateResponse = $this->actingAs($this->editor, 'admin')->put(
            route('admin.cms.posts.update', $scheduledPost),
            [
                'title' => '允许修补正文字眼',
                'body_markdown' => '已更新字眼',
                'type' => 'post',
                'status' => 'published',
                'published_at' => $scheduledPost->published_at->format('Y-m-d H:i'),
                'lock_version' => 1,
            ],
        );
        $normalPublishedUpdateResponse->assertRedirect();

        // 7. 编辑草稿文章并保持草稿 -> 允许通过
        $normalUpdateResponse = $this->actingAs($this->editor, 'admin')->put(
            route('admin.cms.posts.update', $draftPost),
            [
                'title' => '合法修改草稿正文',
                'body_markdown' => '正文已更新',
                'type' => 'post',
                'status' => 'draft',
                'lock_version' => $draftPost->lock_version,
            ],
        );
        $normalUpdateResponse->assertRedirect();
    }

    public function test_markdown_body_preserves_leading_spaces_and_trailing_newlines(): void
    {
        $bodyWithIndent = "    function helloWorld() {\n        return 'preserved';\n    }\n\n";

        $response = $this->actingAs($this->editor, 'admin')->post(route('admin.cms.posts.store'), [
            'title' => '缩进与换行保护测试',
            'body_markdown' => $bodyWithIndent,
            'type' => 'post',
            'status' => 'draft',
        ]);
        $response->assertRedirect();

        $post = Post::query()->where('title', '缩进与换行保护测试')->firstOrFail();
        $this->assertSame($bodyWithIndent, $post->body_markdown);
    }

    public function test_admin_layout_contains_hx_history_false_and_zero_cache_size(): void
    {
        $response = $this->actingAs($this->editor, 'admin')->get(route('admin.cms.posts.index'));
        $response->assertOk();
        $content = (string) $response->getContent();
        $this->assertStringContainsString('hx-history="false"', $content);
        $this->assertStringContainsString('"historyCacheSize": 0', $content);
        $this->assertStringContainsString("sessionStorage.removeItem('htmx-history-cache')", $content);
    }

    public function test_reset_filter_link_waits_for_successful_request_before_removal(): void
    {
        $response = $this->actingAs($this->editor, 'admin')->get(route('admin.cms.posts.index', [
            'search' => 'keyword',
        ]));
        $response->assertOk();
        $content = (string) $response->getContent();

        $this->assertStringContainsString(
            'hx-on::after-request="if (event.detail.successful) this.remove()"',
            $content,
        );
        $this->assertStringNotContainsString(
            'onclick="const f = this.closest(\'form\'); if (f) { f.reset(); f.querySelectorAll(\'input[name=search]\').forEach(el => el.value = \'\'); f.querySelectorAll(\'select\').forEach(el => el.value = \'\'); this.remove(); }"',
            $content,
        );
    }
}
