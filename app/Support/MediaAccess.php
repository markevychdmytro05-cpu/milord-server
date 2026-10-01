<?php

namespace App\Support;

/**
 * Хто може користуватись файловим менеджером: усі, хто редагує контент сайту.
 */
final class MediaAccess
{
    public const PERMISSIONS = [
        'pages_create', 'pages_update', 'modules_create', 'modules_update', 'site_manage',
    ];

    public static function allowed(): bool
    {
        return backpack_user()?->canany(self::PERMISSIONS) ?? false;
    }
}
