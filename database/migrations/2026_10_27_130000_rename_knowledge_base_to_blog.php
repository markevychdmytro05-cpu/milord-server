<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Розділ «База знань» тепер називається «Блог»: назва хаба, його H1 і SEO-заголовок, пункти меню.
 * Адреси на /blog переносить наступна міграція.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rename = fn (?string $value): ?string => is_string($value) ? str_replace('База знань', 'Блог', $value) : $value;

        foreach (DB::table('pages')->where('type', 'page')->where('slug', 'baza-znan')->get(['id', 'title', 'meta_title']) as $page) {
            DB::table('pages')->where('id', $page->id)->update(['title' => $rename($page->title), 'meta_title' => $rename($page->meta_title)]);
        }

        DB::table('menu_items')->where('title', 'База знань')->update(['title' => 'Блог']);

        foreach (Module::all() as $module) {
            $setting = $module->setting->toArray();

            if (($setting['uk']['title'] ?? null) === 'База знань') {
                $setting['uk']['title'] = 'Блог';
                $module->update(['name' => $rename($module->name), 'setting' => $setting]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
