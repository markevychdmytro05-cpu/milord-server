<?php

namespace Tests\Unit;

use App\Models\License;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LicenseContactTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function contacts(): array
    {
        return [
            'telegram handle' => ['@dbecker', 'https://t.me/dbecker'],
            'telegram link without scheme' => ['t.me/dbecker', 'https://t.me/dbecker'],
            'full link kept as is' => ['https://t.me/dbecker/', 'https://t.me/dbecker/'],
            'http link kept as is' => ['http://example.com', 'http://example.com'],
            'any site without scheme' => ['instagram.com/some.user', 'https://instagram.com/some.user'],
            'link with query' => ['wa.me/380671234567?text=hi', 'https://wa.me/380671234567?text=hi'],
            'email' => ['client@example.com', 'mailto:client@example.com'],
            'phone becomes tel' => ['+380 67 123-45-67', 'tel:+380671234567'],
            'viber link kept as is' => ['viber://chat?number=%2B380671234567', 'viber://chat?number=%2B380671234567'],
            'tg link kept as is' => ['tg://resolve?domain=dbecker', 'tg://resolve?domain=dbecker'],
            'whatsapp link kept as is' => ['whatsapp://send?phone=380671234567', 'whatsapp://send?phone=380671234567'],
            'unknown scheme stays text' => ['ftp://example.com', null],
            'data scheme stays text' => ['data:text/html,<b>x</b>', null],
            'plain name stays text' => ['Іван', null],
            'invalid handle stays text' => ['@conroy.odie', null],
            'credentials in url stay text' => ['user@evil.com@site.com', null],
            'javascript is never a link' => ['javascript:alert(1)', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('contacts')]
    public function test_contact_is_converted_to_url(?string $contact, ?string $expectedUrl): void
    {
        $this->assertSame($expectedUrl, (new License(['contact' => $contact]))->contactUrl());
    }

    public function test_contact_link_escapes_html(): void
    {
        $link = (new License(['contact' => '<script>']))->contactLink();

        $this->assertSame('&lt;script&gt;', $link);
    }

    public function test_link_attributes_are_escaped(): void
    {
        $link = (new License(['contact' => 'https://example.com/?a="x"']))->contactLink();

        $this->assertStringNotContainsString('"x"', $link);
    }

    public function test_telegram_link_opens_in_new_tab(): void
    {
        $link = (new License(['contact' => '@dbecker']))->contactLink();

        $this->assertSame('<a href="https://t.me/dbecker" target="_blank" rel="noopener">@dbecker</a>', $link);
    }
}
