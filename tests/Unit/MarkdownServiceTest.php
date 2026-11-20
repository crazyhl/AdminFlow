<?php

namespace Tests\Unit;

use AdminFlow\CMS\Services\MarkdownService;
use Tests\TestCase;

class MarkdownServiceTest extends TestCase
{
    private MarkdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarkdownService();
    }

    public function test_renders_standard_markdown_to_html(): void
    {
        $markdown = "## 二级标题\n\n一段普通的段落，包含 **粗体** 和 *斜体*。";
        $html = $this->service->render($markdown);

        $this->assertStringContainsString('<h2>二级标题</h2>', $html);
        $this->assertStringContainsString('<strong>粗体</strong>', $html);
        $this->assertStringContainsString('<em>斜体</em>', $html);
    }

    public function test_strips_script_tags_and_inline_js_events(): void
    {
        $dangerous = "正文内容<script>alert('xss')</script><img src=\"x\" onerror=\"stealCookie()\" />";
        $html = $this->service->render($dangerous);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('stealCookie', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_strips_javascript_scheme_in_links(): void
    {
        $dangerousLink = '[点击攻击](javascript:alert(document.cookie))';
        $html = $this->service->render($dangerousLink);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_returns_empty_string_for_empty_input(): void
    {
        $this->assertSame('', $this->service->render('   '));
    }

    public function test_converts_soft_breaks_to_hard_breaks_in_paragraphs_and_quotes(): void
    {
        $markdown = "> 第一行引用\n第二行引用\n第三行引用";
        $html = $this->service->render($markdown);

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('第一行引用<br />', $html);
        $this->assertStringContainsString('第二行引用<br />', $html);
        $this->assertStringContainsString('第三行引用', $html);
    }
}
