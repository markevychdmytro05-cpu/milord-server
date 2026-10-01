<?php

use App\Models\Module;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Часті питання про AdsPower живуть лише в FAQ-модулі, а в статті про прогрів курсор згадано без застережень.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->moveFaqToModule();
        $this->updateWarmupArticle();
    }

    public function down(): void
    {
        //
    }

    private function moveFaqToModule(): void
    {
        $page = Page::where('slug', 'nalashtuvannya-adspower')->first();

        if ($page) {
            $page->update(['content' => preg_replace('~<h2>Часті питання</h2>.*?(?=<h2>Читайте також</h2>)~su', '', $page->content)]);
        }

        $module = Module::where('name', 'SEO – FAQ: nalashtuvannya-adspower')->first();

        if (! $module) {
            return;
        }

        $setting = $module->setting->getArrayCopy();
        $items = $setting['items'] ?? [];

        foreach ($items as &$item) {
            if ($item['question'] === 'Яку адресу вказувати?' && ! str_contains($item['description'], 'Інші адреси')) {
                $item['description'] .= ' Інші адреси Numis не приймає.';
            }
        }
        unset($item);

        $known = array_column($items, 'question');
        $additions = [
            ['question' => 'Де взяти ID профілю?', 'description' => 'У списку профілів AdsPower, у колонці з ID. Або імпортуйте профілі в Numis автоматично через API.'],
            ['question' => 'Скільки профілів можна додати?', 'description' => 'Стільки, скільки дозволяє ваш тариф. Кожен профіль у налаштуваннях і в активних завданнях рахується як акаунт.'],
            ['question' => 'Чи треба відкривати профіль вручну?', 'description' => 'Перед стартом продажу профіль має бути відкритий, а в ньому одна вкладка магазину.'],
        ];

        foreach ($additions as $addition) {
            if (! in_array($addition['question'], $known, true)) {
                $items[] = $addition;
            }
        }

        $setting['items'] = array_values($items);
        $module->update(['setting' => $setting]);
    }

    private function updateWarmupArticle(): void
    {
        $page = Page::where('slug', 'progriv-profiliv')->first();

        if (! $page) {
            return;
        }

        $content = str_replace(
            '<li>За потреби відкрийте «Опції та журнал» і ввімкніть випадкові переходи сайтом.</li>',
            '<li>За потреби відкрийте «Опції та журнал» і ввімкніть випадкові переходи сайтом. Показ курсора на сторінці не рекомендовано.</li>',
            $page->content,
        );
        $content = str_replace('width="400" height="253"', 'width="400" height="228"', $content);

        $page->update(['content' => $content]);
    }
};
