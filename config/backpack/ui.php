<?php

return [

    'view_namespace' => 'backpack.theme-coreuiv2::',
    'view_namespace_fallback' => 'backpack.theme-coreuiv2::',

    'default_date_format' => 'D MMM YYYY',
    'default_datetime_format' => 'D MMM YYYY, HH:mm',

    'html_direction' => 'ltr',

    'project_name' => 'Numis',

    'meta_robots_content' => 'noindex, nofollow',

    'home_link' => '',

    'project_logo' => '<b>Numis</b>',

    'breadcrumbs' => true,

    'developer_name' => 'Cristian Tabacitu',

    'developer_link' => 'http://tabacitu.ro',

    'show_powered_by' => true,

    'show_getting_started' => env('APP_ENV') == 'local',

    'styles' => [
        'css/admin.css',
    ],

    'mix_styles' => [
    ],

    'vite_styles' => [
    ],

    'scripts' => [
    ],

    'basset_preload' => [
        'scripts' => [
        ],
        'styles' => [
        ],
    ],

    'mix_scripts' => [
    ],

    'vite_scripts' => [
    ],

    'classes' => [
        'table' => null,

        'tableWrapper' => null,
    ],

];
