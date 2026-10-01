<?php

use App\Models\Module;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Чорний заклик іде впритул до чорного підвалу з формою – форма сама є закликом, тож модуль знімається з головної.
 * Сам модуль лишається в «Модулях» і може бути прикріплений будь-де.
 */
return new class extends Migration
{
    public function up(): void
    {
        $home = Page::where('slug', Page::ROOT_SLUG)->first();
        $cta = Module::where('name', 'Головна – Заклик')->first();

        if ($home && $cta) {
            $home->update(['page_modules' => array_values(array_diff($home->page_modules, [$cta->id]))]);
        }
    }

    public function down(): void
    {
        //
    }
};
