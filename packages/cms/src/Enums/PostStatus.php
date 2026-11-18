<?php

namespace AdminFlow\CMS\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * 获取中文标签
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PUBLISHED => '已发布',
            self::ARCHIVED => '已归档',
        };
    }

    /**
     * 获取徽标对应的颜色风格 (Nordic Minimal 暖调与暗色适配)
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-stone-200/70 text-stone-700 dark:bg-stone-800 dark:text-stone-300',
            self::PUBLISHED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
            self::ARCHIVED => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
        };
    }
}
