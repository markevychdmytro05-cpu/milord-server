<?php

use App\Http\Middleware\EnsureMediaAccess;

return [

    'dir' => ['uploads'],

    'disks' => [
    ],

    'route' => [
        'prefix' => config('backpack.base.route_prefix', 'admin').'/elfinder',
        'middleware' => ['web', config('backpack.base.middleware_key', 'admin'), EnsureMediaAccess::class],
    ],

    'access' => 'Barryvdh\Elfinder\Elfinder::checkAccess',

    'roots' => null,

    'options' => [],

    'root_options' => [
        'uploadOrder' => ['deny', 'allow'],
        'uploadDeny' => ['all'],
        'uploadAllow' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'],
    ],

    'encrypt_mimes' => true,
];
