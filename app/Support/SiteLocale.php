<?php

namespace App\Support;

final class SiteLocale
{
    public static function all(): array
    {
        return config('site.locales', ['uk']);
    }

    public static function current(): string
    {
        return config('site.locale', 'uk');
    }
}
