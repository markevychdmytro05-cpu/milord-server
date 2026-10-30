<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\TranslationLoader\LanguageLine as BaseLanguageLine;

class LanguageLine extends BaseLanguageLine
{
    use CrudTrait;
}
