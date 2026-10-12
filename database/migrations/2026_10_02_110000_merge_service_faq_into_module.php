<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * На сторінках послуг FAQ був і в тексті, і окремим модулем: питання з тексту переходять у модуль-акордеон.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Page::where('type', 'service')->get() as $page) {
            $module = $page->modules_list->first(fn ($module) => str_starts_with($module->name, 'SEO – FAQ'));

            if (! $module || ! preg_match('~<h2[^>]*>\s*Часті питання\s*</h2>(.*?)(?=<h2|$)~su', $page->content, $section)) {
                continue;
            }

            preg_match_all('~<h3[^>]*>(.*?)</h3>\s*<p>(.*?)</p>~su', $section[1], $pairs, PREG_SET_ORDER);

            $setting = $module->setting->getArrayCopy();
            $items = $setting['items'] ?? [];
            $known = array_map(fn (array $item): string => $item['question'] ?? '', $items);

            foreach ($pairs as $pair) {
                $question = trim(strip_tags($pair[1]));

                if (! in_array($question, $known, true)) {
                    array_unshift($items, ['question' => $question, 'description' => trim($pair[2])]);
                }
            }

            $setting['items'] = $items;
            $module->update(['setting' => $setting]);
            $page->update(['content' => rtrim(str_replace($section[0], '', $page->content))]);
        }
    }

    public function down(): void
    {
        //
    }
};
