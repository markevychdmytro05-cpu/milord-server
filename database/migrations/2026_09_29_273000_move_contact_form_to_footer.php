<?php

use App\Models\Module;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Форма зв'язку живе в підвалі, тож показується на кожній сторінці сайту.
 */
return new class extends Migration
{
    public function up(): void
    {
        $module = Module::where('name', 'Головна – Форма зв’язку')->first();

        if (! $module) {
            return;
        }

        foreach (Page::all() as $page) {
            if (in_array($module->id, $page->page_modules, true)) {
                $page->update(['page_modules' => array_values(array_diff($page->page_modules, [$module->id]))]);
            }
        }

        $site = SiteSetting::current();

        if (! in_array($module->id, $site->footer_modules, true)) {
            $site->update(['footer_modules' => [...$site->footer_modules, $module->id]]);
        }

        $module->update(['name' => 'Підвал – Форма зв’язку']);
    }

    public function down(): void
    {
        //
    }
};
