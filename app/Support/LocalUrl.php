<?php

namespace App\Support;

/**
 * Зберігає посилання на власні файли шляхом від кореня, щоб вони не ламались при зміні домену.
 */
final class LocalUrl
{
    public static function path(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $root = rtrim(url('/'), '/');

        return str_starts_with($value, $root.'/') ? substr($value, strlen($root)) : $value;
    }
}
