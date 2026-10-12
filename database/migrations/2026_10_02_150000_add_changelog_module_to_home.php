<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Блок «Історія версій» на головній: останні записи зі сторінки «Що нового». */
return new class extends Migration
{
    public function up(): void
    {
        $template = ModuleTemplate::firstOrCreate(['code' => 'changelog'], [
            'name' => 'Changelog (історія версій)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                    'limit' => ['label' => 'Скільки останніх записів показати', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                    'limit' => 'nullable|integer|between:1,10',
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Заголовок', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'nullable|string|max:120',
                ],
            ],
        ]);

        $home = DB::table('pages')->where('slug', '/')->value('id');

        if (! $home || $template->modules()->where('name', 'Головна – Історія версій')->exists()) {
            return;
        }

        $module = $template->makeModule('Головна – Історія версій', [
            'active' => 1,
            'anchor' => 'changelog',
            'limit' => 2,
            'uk' => ['title' => 'Що нового в Numis'],
        ]);

        $faqOrder = DB::table('page_module')
            ->where('page_id', $home)
            ->where('module_id', DB::table('modules')->where('name', 'Головна – FAQ')->value('id'))
            ->value('sort_order');

        DB::table('page_module')->where('page_id', $home)->where('sort_order', '>=', $faqOrder)->increment('sort_order');
        DB::table('page_module')->insert(['page_id' => $home, 'module_id' => $module->id, 'sort_order' => $faqOrder]);
    }

    public function down(): void
    {
        //
    }
};
