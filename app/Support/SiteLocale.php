<?php

namespace App\Support;

/**
 * Мови контенту сайту (для полів модулів, що перекладаються).
 */
final class SiteLocale
{
    /** @return list<string> */
    public static function all(): array
    {
        return config('site.locales', ['uk']);
    }

    public static function current(): string
    {
        return config('site.locale', 'uk');
    }
}
