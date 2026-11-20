<?php

namespace AdminFlow\CMS\Services;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use AdminFlow\CMS\Models\Post;
use AdminFlow\CMS\Services\Concerns\AppliesPostUpdates;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PostDataResolver
{
    use AppliesPostUpdates;

    /**
     * 解析内容类型枚举
     */
    public function resolveType(mixed $type): PostType
    {
        return $type instanceof PostType ? $type : PostType::tryFrom((string) $type) ?? PostType::POST;
    }

    /**
     * 解析状态枚举
     */
    public function resolveStatus(mixed $status): PostStatus
    {
        return $status instanceof PostStatus ? $status : PostStatus::tryFrom((string) $status) ?? PostStatus::DRAFT;
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
     * 解析新建文章所需的基础属性
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function resolveCreateAttributes(
        array $data,
        \AdminFlow\Core\Models\User $author,
        ?string $customSlug,
        PostSlugService $slugService,
    ): array {
        $status = $this->resolveStatus($data['status'] ?? null);

        return [
            'user_id' => $author->id,
            'title' => trim($data['title']),
            'slug' => $this->resolveCreateSlug($data['title'], $customSlug, $slugService),
            'summary' => $this->resolveNullableString($data['summary'] ?? null),
            'body_markdown' => (string) ($data['body_markdown'] ?? ''),
            'body_html' => (string) ($data['body_html'] ?? ''),
            'cover_image_url' => $this->resolveNullableString($data['cover_image_url'] ?? null),
            'type' => $this->resolveType($data['type'] ?? null),
            'status' => $status,
            'published_at' => $this->resolvePublishedAt($status, $data['published_at'] ?? null),
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'allow_comments' => (bool) ($data['allow_comments'] ?? true),
            'lock_version' => 1,
        ];
    }

    private function resolveNullableString(mixed $value): ?string
    {
        return $value !== null && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    /**
     * 判断是否修改了已发布内容的发布时间
     */
    public function isPublishedAtModified(Post $post, ?string $inputDate): bool
    {
        if ($inputDate === null || trim($inputDate) === '') {
            return false;
        }

        try {
            $parsed = Carbon::parse($inputDate);
        } catch (\Throwable) {
            return false;
        }

        return $post->published_at?->format('Y-m-d H:i') !== $parsed->format('Y-m-d H:i');
    }

    /**
     * 校验更新操作是否涉及发布授权（状态变更或修改已发布内容的发布时间）
     */
    public function requiresPublishAuthorization(Post $post, mixed $newStatus, ?string $newPublishedAt): bool
    {
        $newIsPublished = $newStatus === PostStatus::PUBLISHED || $newStatus === PostStatus::PUBLISHED->value;
        $isStatusToggled = $newIsPublished !== ($post->status === PostStatus::PUBLISHED);
        $isPublishDateModified =
            ($post->status === PostStatus::PUBLISHED || $newIsPublished)
            && $this->isPublishedAtModified($post, $newPublishedAt);

        return $isStatusToggled || $isPublishDateModified;
    }
}
