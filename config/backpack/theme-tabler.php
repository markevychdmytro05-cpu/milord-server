<?php

return [
    'layout' => 'vertical',

    'auth_layout' => 'default',

    'styles' => [
        base_path('vendor/backpack/theme-tabler/resources/assets/css/skins/backpack-color-palette.css'),

    ],

    'options' => [
        'colorModes' => [
            'system' => 'la-desktop',
            'light' => 'la-sun',
            'dark' => 'la-moon',
        ],

        'defaultColorMode' => 'light',

        'showColorModeSwitcher' => true,

        'useStickyHeader' => false,

        'useFluidContainers' => true,

        'sidebarFixed' => false,

        'doubleTopBarInHorizontalLayouts' => false,

        'showPasswordVisibilityToggler' => false,
    ],

    'classes' => [
        'body' => null,

        'topHeader' => null,

        'sidebar' => null,

        'menuHorizontalContainer' => null,

        'menuHorizontalContent' => null,

        'footer' => null,

        'table' => null,

        'tableWrapper' => null,
    ],

];
