<?php

use Backpack\CRUD\app\Library\Uploaders\MultipleFiles;
use Backpack\CRUD\app\Library\Uploaders\SingleBase64Image;
use Backpack\CRUD\app\Library\Uploaders\SingleFile;
use Backpack\CRUD\app\Library\Uploaders\Support\FileExtensions;
use Backpack\CRUD\app\Library\Uploaders\Support\FileNameGenerator;

return [

    'show_translatable_field_icon' => true,
    'translatable_field_icon_position' => 'right',

    'locales' => [
        'en' => 'English',
        'fr' => 'French',
        'it' => 'Italian',
        'ro' => 'Romanian',
    ],

    'view_namespaces' => [
        'buttons' => [
            'crud::buttons',
        ],
        'columns' => [
            'crud::columns',
        ],
        'fields' => [
            'crud::fields',
        ],
        'filters' => [
            'crud::filters',
        ],
    ],
    'uploaders' => [
        'withFiles' => [
            'image' => SingleBase64Image::class,
            'upload' => SingleFile::class,
            'upload_multiple' => MultipleFiles::class,
        ],
    ],

    'file_name_generator' => FileNameGenerator::class,

    'allowed_upload_extensions' => FileExtensions::DEFAULT_ALLOWED,

];
