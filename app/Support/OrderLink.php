<?php

namespace App\Support;

use App\Models\SiteSetting;

final class OrderLink
{
    public static function url(): ?string
    {
        return config('license.contact_url') ?: SiteSetting::current()->telegramUrl();
    }
}
