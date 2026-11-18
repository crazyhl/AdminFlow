<?php

namespace AdminFlow\CMS\Services;

use AdminFlow\CMS\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class PostSlugService
{
    /**
     * 生成并保证内容 Slug 唯一性
     */
    public function generate(string $title, ?string $providedSlug = null, ?int $ignoreId = null): string
    {
        $candidate = trim((string) $providedSlug);
        if ($candidate === '') {
            $slugified = Str::slug($title);
            $candidate = $slugified !== '' ? $slugified : (string) Str::uuid();
        }

        $candidate = Str::substr($candidate, 0, 255);
        $base = $candidate;
        $counter = 1;

        while ($this->exists($candidate, $ignoreId)) {
            $suffix = "-{$counter}";
            $maxBaseLength = max(1, 255 - strlen($suffix));
            $trimmedBase = rtrim(Str::substr($base, 0, $maxBaseLength), '-');
            if ($trimmedBase === '') {
                $trimmedBase = Str::substr($base, 0, $maxBaseLength);
            }
            $candidate = "{$trimmedBase}{$suffix}";
            $counter++;
        }

        return $candidate;
    }

    /**
     * 检查 Slug 是否已被占用
     */
    protected function exists(string $slug, ?int $ignoreId = null): bool
    {
        $query = Post::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * 判定是否由 Slug 唯一约束冲突导致的数据库异常
     */
    public function isUniqueViolation(QueryException $e): bool
    {
        $message = $e->getMessage();
        $errorCode = (int) ($e->errorInfo[1] ?? 0);

        $isUniqueError =
            $errorCode === 1062
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate key');

        if (!$isUniqueError) {
            return false;
        }

        // 必须核对是否针对 slug 字段或 cms_posts_slug_unique 唯一索引，避免 SQL 中的字段名引起误判
        if (str_contains($message, 'cms_posts_slug_unique') || str_contains($message, 'cms_posts.slug')) {
            return true;
        }

        return (bool) preg_match('/(?:for key|constraint) [^\n;]*slug/i', $message);
    }
}
