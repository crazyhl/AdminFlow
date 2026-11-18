<?php

namespace AdminFlow\CMS\Services;

use Illuminate\Support\Str;

class MarkdownService
{
    /**
     * 将 Markdown 文本安全渲染为 HTML
     *
     * 遵循 P1-04 安全准则：
     * 1. 禁用或剥离原始不安全 HTML 标签（如 <script>, <style>, <iframe> 等）。
     * 2. 禁止不安全的 URI scheme（如 javascript:, vbscript:）。
     * 3. 剥离内联 JS 事件属性（如 onclick, onerror, onload 等）。
     */
    public function render(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        // 使用 Laravel 内置 CommonMark 管道，剥离危险的原始 HTML 与不安全链接协议
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 30,
        ]);

        return $this->sanitize($html);
    }

    /**
     * 对渲染后的 HTML 进行二次净化与防御性过滤
     */
    public function sanitize(string $html): string
    {
        // 彻底移除任何可能遗漏的 javascript: 协议
        $html = preg_replace('/href=([\'"])\s*javascript:[^>]*?\1/i', 'href="#"', $html) ?? $html;
        $html = preg_replace('/src=([\'"])\s*javascript:[^>]*?\1/i', 'src=""', $html) ?? $html;

        // 移除内联 on* 事件监听器 (onerror, onload, onclick, onmouseover 等)
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;

        return trim($html);
    }
}
