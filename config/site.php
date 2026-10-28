<?php

return [
    /*
     * Мови контенту модулів. Поля lang_fields шаблонів модулів зберігаються
     * окремо для кожної мови (setting[uk][title] тощо).
     */
    'locales' => ['uk'],

    'locale' => 'uk',

    'lead_notification_email' => env('LEAD_NOTIFICATION_EMAIL'),

    // За Cloudflare чи іншим проксі: «cloudflare» або список IP/CIDR через кому (див. AppServiceProvider).
    'trusted_proxies' => env('TRUSTED_PROXIES'),
];
