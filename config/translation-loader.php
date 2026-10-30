<?php

use App\Models\LanguageLine;
use Spatie\TranslationLoader\TranslationLoaderManager;
use Spatie\TranslationLoader\TranslationLoaders\Db;

return [

    'translation_loaders' => [
        Db::class,
    ],

    'model' => LanguageLine::class,

    'translation_manager' => TranslationLoaderManager::class,

];
