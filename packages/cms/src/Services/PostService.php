<?php

namespace AdminFlow\CMS\Services;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use AdminFlow\CMS\Exceptions\ConcurrencyConflictException;
use AdminFlow\CMS\Models\Post;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostService
{
    public function __construct(
        protected MarkdownService $markdownService,
        protected PostDataResolver $dataResolver,
        protected PostSlugService $slugService,
    ) {}

    /**
     * 分页查询内容列表
     *
     * @param array{
     *     search?: string|null,
     *     status?: string|null,
     *     type?: string|null
     * } $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Post::query()->with('author');

        $rawSearch = $filters['search'] ?? null;
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        $query->when($search !== '', static function ($q) use ($search): void {
            $q->where(static function ($inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            });
        });

        $rawStatus = $filters['status'] ?? null;
        $status = is_string($rawStatus) ? PostStatus::tryFrom($rawStatus)?->value : null;
        $query->when($status !== null, static fn($q) => $q->where('status', $status));

        $rawType = $filters['type'] ?? null;
        $type = is_string($rawType) ? PostType::tryFrom($rawType)?->value : null;
        $query->when($type !== null, static fn($q) => $q->where('type', $type));

        return $query->orderByDesc('is_pinned')->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * 获取全站内容统计指标（单次聚合查询，避免 Blade 额外多次 count()）
     *
     * @return array{
     *     total: int,
     *     published: int,
     *     draft: int,
     *     pages: int
     * }
     */
    public function getStatistics(): array
    {
        $raw = Post::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as published', [PostStatus::PUBLISHED->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft', [PostStatus::DRAFT->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as pages', [PostType::PAGE->value])
            ->first();

        return [
            'total' => (int) ($raw->total ?? 0),
            'published' => (int) ($raw->published ?? 0),
            'draft' => (int) ($raw->draft ?? 0),
            'pages' => (int) ($raw->pages ?? 0),
        ];
    }

    /**
     * 创建文章或独立页面（自动生成的链接遇唯一键冲突支持有限重试，手填链接返回字段错误）
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function create(array $data, User $author): Post
    {
        $data = $this->dataResolver->preRenderMarkdown($data, $this->markdownService);
        $providedSlug = trim((string) ($data['slug'] ?? ''));
        $customSlug = $providedSlug !== '' ? $providedSlug : null;

        if ($customSlug !== null) {
            return $this->createWithCustomSlug($data, $author, $customSlug);
        }

        return $this->createWithAutoSlug($data, $author);
    }

    /**
     * 手填固定链接创建（冲突直接返回校验异常）
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    private function createWithCustomSlug(array $data, User $author, string $customSlug): Post
    {
        try {
            return $this->executeCreateTransaction($data, $author, $customSlug);
        } catch (QueryException $e) {
            if ($this->slugService->isUniqueViolation($e)) {
                throw ValidationException::withMessages([
                    'slug' => '该固定链接已被其他内容占用，请更换',
                ]);
            }

            throw $e;
        }
    }

    /**
     * 自动生成固定链接创建（遇唯一键冲突有限重试）
     *
     * @param array<string, mixed> $data
     */
    private function createWithAutoSlug(array $data, User $author): Post
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->executeCreateTransaction($data, $author, null);
            } catch (QueryException $e) {
                if (!$this->slugService->isUniqueViolation($e) || $attempt >= 3) {
                    throw $e;
                }

                usleep(10_000);
            }
        }

        throw new \RuntimeException('文章创建重试耗尽');
    }

    /**
     * 执行内容创建事务与审计记录
     *
     * @param array<string, mixed> $data
     */
    private function executeCreateTransaction(array $data, User $author, ?string $customSlug = null): Post
    {
        return DB::transaction(function () use ($data, $author, $customSlug): Post {
            $attributes = $this->dataResolver->resolveCreateAttributes($data, $author, $customSlug, $this->slugService);
            $post = Post::query()->create($attributes);

            AuditLogger::record(
                action: 'cms.posts.create',
                target: $post,
                details: [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'type' => $post->type->value,
                    'status' => $post->status->value,
                ],
                userId: $author->id,
            );

            return $post;
        });
    }

    /**
     * 更新内容（带乐观并发锁校验与 Markdown 锁外预渲染）
     *
     * @param array<string, mixed> $data
     * @throws ConcurrencyConflictException
     * @throws ValidationException
     */
    public function update(Post $post, array $data, int $expectedVersion, ?User $actor = null): Post
    {
        // 缩短持锁时间：在进入数据库行锁事务前提前完成长 Markdown 解析渲染
        $data = $this->dataResolver->preRenderMarkdown($data, $this->markdownService);

        try {
            return DB::transaction(function () use ($post, $data, $expectedVersion, $actor): Post {
                $fresh = $this->lockAndValidateVersion($post->id, $expectedVersion);

                $beforeState = [
                    'title' => $fresh->title,
                    'status' => $fresh->status->value,
                    'lock_version' => $fresh->lock_version,
                ];

                $this->dataResolver->applyUpdates($fresh, $data, $this->markdownService);

                $fresh->lock_version++;
                $fresh->save();

                AuditLogger::record(
                    action: 'cms.posts.update',
                    target: $fresh,
                    details: [
                        'before' => $beforeState,
                        'after' => [
                            'title' => $fresh->title,
                            'status' => $fresh->status->value,
                            'lock_version' => $fresh->lock_version,
                        ],
                    ],
                    userId: $actor?->id,
                );

                return $fresh;
            });
        } catch (QueryException $e) {
            if ($this->slugService->isUniqueViolation($e)) {
                throw ValidationException::withMessages([
                    'slug' => '该固定链接已被其他内容占用，请更换',
                ]);
            }

            throw $e;
        }
    }

    /**
     * 变更内容发布状态
     */
    public function transitionStatus(Post $post, PostStatus $newStatus, int $expectedVersion, ?User $actor = null): Post
    {
        return DB::transaction(function () use ($post, $newStatus, $expectedVersion, $actor): Post {
            $fresh = $this->lockAndValidateVersion($post->id, $expectedVersion);

            $oldStatus = $fresh->status;
            $fresh->status = $newStatus;

            if ($newStatus === PostStatus::PUBLISHED && $fresh->published_at === null) {
                $fresh->published_at = Carbon::now();
            }

            $fresh->lock_version++;
            $fresh->save();

            AuditLogger::record(
                action: "cms.posts.status_{$newStatus->value}",
                target: $fresh,
                details: [
                    'from' => $oldStatus->value,
                    'to' => $newStatus->value,
                    'lock_version' => $fresh->lock_version,
                ],
                userId: $actor?->id,
            );

            return $fresh;
        });
    }

    /**
     * 删除内容（带并发锁校验）
     *
     * @throws ConcurrencyConflictException
     */
    public function delete(Post $post, int $expectedVersion, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($post, $expectedVersion, $actor): bool {
            $fresh = $this->lockAndValidateVersion($post->id, $expectedVersion);

            AuditLogger::record(
                action: 'cms.posts.delete',
                target: $fresh,
                details: [
                    'id' => $fresh->id,
                    'title' => $fresh->title,
                    'slug' => $fresh->slug,
                    'lock_version' => $fresh->lock_version,
                ],
                userId: $actor?->id,
            );

            return (bool) $fresh->delete();
        });
    }

    /**
     * 行锁查询并严格核验乐观锁版本（收拢统一校验逻辑）
     *
     * @throws ConcurrencyConflictException
     */
    private function lockAndValidateVersion(int $id, int $expectedVersion): Post
    {
        /** @var Post $fresh */
        $fresh = Post::query()->where('id', $id)->lockForUpdate()->firstOrFail();

        if ($fresh->lock_version !== $expectedVersion) {
            throw new ConcurrencyConflictException($expectedVersion, $fresh->lock_version);
        }

        return $fresh;
    }
}
