<?php

namespace AdminFlow\CMS\Models;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use AdminFlow\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * AdminFlow CMS 内容模型（文章与独立页面）
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $body_markdown
 * @property string|null $body_html
 * @property string|null $cover_image_url
 * @property PostType $type
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property bool $is_pinned
 * @property bool $allow_comments
 * @property int $lock_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 */
class Post extends Model
{
    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'cms_posts';

    /**
     * 可批量赋值的字段
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'summary',
        'body_markdown',
        'body_html',
        'cover_image_url',
        'type',
        'status',
        'published_at',
        'is_pinned',
        'allow_comments',
        'lock_version',
    ];

    /**
     * 字段类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'is_pinned' => 'boolean',
            'allow_comments' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    /**
     * 内容作者关联
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 作用域：仅已发布内容
     *
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * 作用域：仅常规文章
     *
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopePosts(Builder $query): Builder
    {
        return $query->where('type', PostType::POST);
    }

    /**
     * 作用域：仅独立页面
     *
     * @param Builder<$this> $query
     * @return Builder<$this>
     */
    public function scopePages(Builder $query): Builder
    {
        return $query->where('type', PostType::PAGE);
    }

    /**
     * 判定是否已公开发布
     */
    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISHED && $this->published_at !== null && $this->published_at->isPast();
    }

    /**
     * 判定是否为草稿
     */
    public function isDraft(): bool
    {
        return $this->status === PostStatus::DRAFT;
    }

    /**
     * 判定是否已归档
     */
    public function isArchived(): bool
    {
        return $this->status === PostStatus::ARCHIVED;
    }
}
