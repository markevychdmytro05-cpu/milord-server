<?php

use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Хаб «База знань» стає сіткою карток: статтям задаються категорії та дати, до хабу прикріплюється модуль Articles.
 */
return new class extends Migration
{
    private const CATEGORIES = [
        'nalashtuvannya-adspower' => 'Налаштування',
        'kabinet-nbu' => 'Налаштування',
        'test-povedinky' => 'Налаштування',
        'cherha-kapcha-429' => 'Налаштування',
        'yak-kupyty-monetu-nbu-pershym' => 'Купівля',
        'probnyi-period' => 'Ліцензія',
        'licenziya-ta-pristroyi' => 'Ліцензія',
        'bezpeka-danykh' => 'Ліцензія',
    ];

    public function up(): void
    {
        $hub = Page::where('slug', 'help')->first();

        if (! $hub || Module::where('name', 'База знань – Статті')->exists()) {
            return;
        }

        $day = 0;
        foreach (self::CATEGORIES as $slug => $category) {
            Page::where('slug', $slug)->update(['category' => $category, 'published_at' => now()->subDays(3 * $day++)]);
        }

        $module = ModuleTemplate::byCode('articles')->makeModule('База знань – Статті', [
            'active' => 1,
            'show_filters' => 1,
            'limit' => 24,
            'uk' => [
                'title' => '',
                'description' => '',
                'all_label' => 'Усі статті',
                'empty_text' => 'Статей поки немає.',
            ],
        ]);

        $hub->update([
            'content' => '<p>Інструкції з налаштування, запуску та ліцензування: від підключення AdsPower до пробного періоду.</p>',
            'page_modules' => [...$hub->page_modules, $module->id],
        ]);
    }

    public function down(): void
    {
        Module::where('name', 'База знань – Статті')->delete();
        Page::whereNotNull('category')->update(['category' => null, 'published_at' => null]);
    }
};
