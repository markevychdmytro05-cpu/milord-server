<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $coins = DB::table('modules')->where('name', 'Головна – Найближчі випуски')->value('id');
        $faq = DB::table('modules')->where('name', 'Головна – FAQ')->value('id');
        $home = DB::table('pages')->where('slug', '/')->value('id');

        if ($coins && $faq && $home) {
            $coinsSortOrder = DB::table('page_module')->where('page_id', $home)->where('module_id', $coins)->value('sort_order');
            $faqSortOrder = DB::table('page_module')->where('page_id', $home)->where('module_id', $faq)->value('sort_order');

            if ($coinsSortOrder !== null && $faqSortOrder !== null && $coinsSortOrder < $faqSortOrder) {
                DB::table('page_module')->where('page_id', $home)->where('module_id', $coins)->update(['sort_order' => $faqSortOrder]);
                DB::table('page_module')->where('page_id', $home)->where('module_id', $faq)->update(['sort_order' => $coinsSortOrder]);
            }
        }

        DB::table('site_settings')->update(['footer_heading' => null]);
    }

    public function down(): void
    {
    }
};
