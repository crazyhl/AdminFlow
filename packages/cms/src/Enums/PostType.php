<?php

namespace AdminFlow\CMS\Enums;

enum PostType: string
{
    case POST = 'post';
    case PAGE = 'page';

    /**
     * 获取中文标签
     */
    public function label(): string
    {
        return match ($this) {
            self::POST => '文章',
            self::PAGE => '独立页面',
        };
    }
}
