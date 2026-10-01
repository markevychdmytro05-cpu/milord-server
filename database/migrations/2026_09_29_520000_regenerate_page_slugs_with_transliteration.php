<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Перегенерувати slug усіх сторінок (крім головної) транслітерацією українських заголовків. */
    public function up(): void
    {
        Page::where('slug', '!=', Page::ROOT_SLUG)->orderBy('id')->get()->each(function (Page $page): void {
            $page->slug = null;
            $page->save();
        });
    }

    public function down(): void
    {
        //
    }
};
