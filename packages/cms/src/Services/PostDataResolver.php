<?php

namespace AdminFlow\CMS\Services;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use AdminFlow\CMS\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PostDataResolver
{
    /**
     * 解析内容类型枚举
     */
    public function resolveType(mixed $type): PostType
    {
        if ($type instanceof PostType) {
            return $type;
        }

        return PostType::tryFrom((string) $type) ?? PostType::POST;
    }

    /**
     * 解析状态枚举
     */
    public function resolveStatus(mixed $status): PostStatus
    {
        if ($status instanceof PostStatus) {
            return $status;
        }

        return PostStatus::tryFrom((string) $status) ?? PostStatus::DRAFT;
    }

    /**
     * 解析发布时间
     */
    public function resolvePublishedAt(PostStatus $status, mixed $publishedAt, ?Carbon $fallback = null): ?Carbon
    {
        if (!empty($publishedAt)) {
            return Carbon::parse($publishedAt);
        }

        if ($status === PostStatus::PUBLISHED) {
            return $fallback ?? Carbon::now();
        }

        return null;
    }

    /**
     * 解析新建内容固定链接（手填原样校验占用，留空按标题自动生成）
     *
     * @throws ValidationException
     */
    public function resolveCreateSlug(string $title, ?string $customSlug, PostSlugService $slugService): string
    {
        if ($customSlug === null || trim($customSlug) === '') {
            return $slugService->generate($title, null);
        }

        $slug = trim($customSlug);
        if (Post::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => '该固定链接已被其他内容占用，请更换',
            ]);
        }

        return $slug;
    }

    /**
     * 更新基本标题与固定链接
     *
     * @param array<string, mixed> $data
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
            $post->summary = $data['summary'] !== null ? trim((string) $data['summary']) : null;
        }
    }

    /**
     * 提前渲染 Markdown 正文，缩短写操作数据库行锁持有时间
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function preRenderMarkdown(array $data, MarkdownService $markdownService): array
    {
        if (isset($data['body_markdown'])) {
            $data['body_html'] = $markdownService->render((string) $data['body_markdown']);
        }

        return $data;
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
            $post->cover_image_url = $data['cover_image_url'] !== null ? trim((string) $data['cover_image_url']) : null;
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

        if ($post->status === PostStatus::PUBLISHED && $post->published_at === null) {
            $post->published_at = Carbon::now();
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
