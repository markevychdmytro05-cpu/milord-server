<?php

use App\Http\Middleware\AuthenticateBackpackSession;
use App\Http\Middleware\CheckIfAdmin;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;

return [

    'registration_open' => env('BACKPACK_REGISTRATION_OPEN', env('APP_ENV') === 'local'),

    'route_prefix' => 'admin',

    'web_middleware' => 'web',
    'setup_auth_routes' => true,

    'setup_dashboard_routes' => true,

    'setup_my_account_routes' => true,

    'setup_password_recovery_routes' => false,

    'setup_email_verification_routes' => false,

    'setup_email_verification_middleware' => true,

    'email_verification_route_names' => [
        'notice' => 'verification.notice',
        'verify' => 'verification.verify',
        'send' => 'verification.send',
    ],

    'email_verification_throttle_access' => '3,15',

    'password_recovery_throttle_notifications' => 600,

    'password_recovery_token_expiration' => 60,

    'password_recovery_throttle_access' => '3,10',

    'user_model_fqn' => config('auth.providers.users.model'),

    'middleware_class' => [
        CheckIfAdmin::class,
        ConvertEmptyStringsToNull::class,
        AuthenticateBackpackSession::class,
    ],

    'middleware_key' => 'admin',

    'authentication_column' => 'email',
    'authentication_column_name' => 'Email',

    'email_column' => 'email',

    'guard' => 'backpack',

    'passwords' => 'backpack',

    'avatar_type' => 'gravatar',

    'gravatar_fallback' => 'blank',

    'root_disk_name' => 'root',

    'useDatabaseTransactions' => false,

    'token_username' => env('BACKPACK_TOKEN_USERNAME', false),

    'project' => env('BACKPACK_PROJECT', false),
];
