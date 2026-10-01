<?php

namespace Tests\Unit;

use App\Support\SafeHtml;
use PHPUnit\Framework\TestCase;

class SafeHtmlTest extends TestCase
{
    public function test_keeps_article_markup_and_safe_media(): void
    {
        $html = '<h2>Заголовок</h2><p>Текст <a href="https://example.com" target="_blank">джерело</a></p><figure><img src="/images/app/demo.png" alt="Фото" width="800"></figure>';

        $clean = SafeHtml::clean($html);

        $this->assertStringContainsString('<h2>Заголовок</h2>', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('src="/images/app/demo.png"', $clean);
    }

    public function test_removes_active_markup_and_unsafe_urls(): void
    {
        $html = '<p onclick="alert(1)">Текст <a href="javascript:alert(2)">лінк</a></p><script>alert(3)</script><img src="data:image/svg+xml;base64,AA" onerror="alert(4)"><a href="/\evil.example">хибний лінк</a>';

        $clean = SafeHtml::clean($html);

        $this->assertStringContainsString('Текст', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('alert(3)', $clean);
        $this->assertStringNotContainsString('data:image', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('href="/\evil.example"', $clean);
    }
}
