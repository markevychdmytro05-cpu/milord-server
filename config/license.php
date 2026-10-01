<?php

return [
    'signing_secret_key' => env('LICENSE_SIGNING_SECRET_KEY'),

    'require_signature' => (bool) env('LICENSE_REQUIRE_SIGNATURE', false),

    'offline_grace_hours' => (int) env('LICENSE_OFFLINE_GRACE_HOURS', 72),

    'rate_limit' => (int) env('LICENSE_API_RATE_LIMIT', 30),

    // Посилання для замовлення ліцензії (Telegram, форма тощо). Порожнє – кнопки на сайті приховані.
    'contact_url' => env('LICENSE_CONTACT_URL'),
];
