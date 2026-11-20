<?php

namespace AdminFlow\CMS\Services\Concerns;

use AdminFlow\CMS\Models\Post;
use AdminFlow\CMS\Services\MarkdownService;
use Illuminate\Validation\ValidationException;

trait AppliesPostUpdates
{
    /**
     * 更新基本标题与固定链接
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    protected function updateBasicInfo(Post $post, array $data): void
    {
        if (isset($data['title'])) {
            $post->title = trim((string) $data['title']);
        }

        if (isset($data['slug'])) {
            $newSlug = trim((string) $data['slug']);
            if ($newSlug !== $post->slug) {
                if (Post::query()->where('slug', $newSlug)->where('id', '!=', $post->id)->exists()) {
                    throw ValidationException::withMessages([
                        'slug' => "固定链接 [{$newSlug}] 已被其他内容占用",
                    ]);
                }
                $post->slug = $newSlug;
            }
        }

        if (array_key_exists('summary', $data)) {
            $post->summary = $this->resolveNullableString($data['summary']);
        }
    }

    /**
     * 更新正文与媒体属性
     *
     * @param array<string, mixed> $data
     */
    protected function updateContentAndMedia(Post $post, array $data, MarkdownService $markdownService): void
    {
        if (isset($data['body_markdown'])) {
            $post->body_markdown = (string) $data['body_markdown'];
            $post->body_html = isset($data['body_html'])
                ? (string) $data['body_html']
                : $markdownService->render($post->body_markdown);
        }

        if (array_key_exists('cover_image_url', $data)) {
            $post->cover_image_url = $this->resolveNullableString($data['cover_image_url']);
        }

        if (isset($data['type'])) {
            $post->type = $this->resolveType($data['type']);
        }
    }

    /**
     * 更新发布状态与行为开关
     *
     * @param array<string, mixed> $data
     */
    protected function updatePublishAndSettings(Post $post, array $data): void
    {
        if (isset($data['status'])) {
            $post->status = $this->resolveStatus($data['status']);
        }

        if (array_key_exists('published_at', $data)) {
            $post->published_at = $this->resolvePublishedAt($post->status, $data['published_at'], $post->published_at);
        }

        if (isset($data['is_pinned'])) {
            $post->is_pinned = (bool) $data['is_pinned'];
        }

        if (isset($data['allow_comments'])) {
            $post->allow_comments = (bool) $data['allow_comments'];
        }
    }

    /**
     * 将业务更新属性映射至模型
     *
     * @param array<string, mixed> $data
     */
    public function applyUpdates(Post $post, array $data, MarkdownService $markdownService): void
    {
        $this->updateBasicInfo($post, $data);
        $this->updateContentAndMedia($post, $data, $markdownService);
        $this->updatePublishAndSettings($post, $data);
    }
}
