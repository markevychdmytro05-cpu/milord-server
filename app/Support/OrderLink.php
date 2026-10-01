<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Посилання для замовлення ключа: явно задане в конфігурації або Telegram із налаштувань сайту.
 */
final class OrderLink
{
    public static function url(): ?string
    {
        return config('license.contact_url') ?: SiteSetting::current()->telegramUrl();
    }
}
