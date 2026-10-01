<?php

use App\Models\ModuleTemplate;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * SEO: змістовні заголовки службових сторінок і опис зображення в hero (поле image_alt).
 */
return new class extends Migration
{
    private const HERO_ALT = 'Інтерфейс Numis: список завдань автоматичної купівлі монет НБУ';

    private const TITLES = [
        'about' => 'Про Numis: програма для автокупівлі монет НБУ',
        'privacy' => 'Політика конфіденційності Numis',
        'terms' => 'Публічна оферта Numis: умови ліцензії',
    ];

    public function up(): void
    {
        foreach (self::TITLES as $slug => $title) {
            Page::where('slug', $slug)->whereNull('meta_title')->update(['meta_title' => $title]);
        }

        $template = ModuleTemplate::byCode('hero');

        if (! $template) {
            return;
        }

        $config = $template->template->toArray();
        $config['lang_fields'] += ['image_alt' => ['label' => 'Опис зображення (alt)', 'input' => 'text']];
        $config['lang_rules'] += ['image_alt' => 'nullable|string|max:160'];
        $template->update(['template' => $config]);

        foreach ($template->modules as $module) {
            $setting = $module->setting->toArray();
            foreach (array_keys($setting) as $key) {
                if (is_array($setting[$key]) && array_key_exists('title_1', $setting[$key]) && ! empty($setting['image'])) {
                    $setting[$key]['image_alt'] ??= self::HERO_ALT;
                }
            }
            $module->update(['setting' => $setting]);
        }
    }

    public function down(): void
    {
        //
    }
};
