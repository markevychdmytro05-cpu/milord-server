<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\TranslationLoader\LanguageLine as BaseLanguageLine;

/**
 * Рядок перекладу в базі: group + key і тексти за мовами (text = {"uk": "…"}).
 * Значення з бази перекривають файли lang/ (spatie/laravel-translation-loader).
 */
class LanguageLine extends BaseLanguageLine
{
    use CrudTrait;
}
