<?php

use App\Models\Module;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Скріншоти програми в статтях бази знань, стаття про прогрів профілів (стара назва «Тест поведінки»)
 * та FAQ без згадок CAPTCHA й застережень про гарантії.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->cleanFaqModules();
        $this->rewriteWarmupArticle();
        $this->addScreenshots();

        Page::where('slug', 'ne-dodaje-v-koshyk')->update(['og_image' => '/images/kb/cherha-ta-429.jpg']);
        Page::where('slug', 'yak-kupyty-monetu-nbu-pershym')->each(fn (Page $page) => $page->update([
            'content' => str_replace('(за замовчуванням 2 хвилини)', '(за замовчуванням 5 хвилин)', $page->content),
        ]));
    }

    public function down(): void
    {
        //
    }

    private function cleanFaqModules(): void
    {
        $overrides = [
            'SEO – FAQ: ne-dodaje-v-koshyk' => [
                ['question' => 'Чому монета не додається в кошик?', 'description' => 'Найчастіше причина в черзі, обмеженні запитів (429) на боці магазину, закритому профілі або хибному часі старту.'],
                ['question' => 'Що перевірити першим?', 'description' => 'Чи відкритий профіль AdsPower і чи вказано правильний час за київським часом.'],
                ['question' => 'Як перевірити налаштування без купівлі?', 'description' => 'Запустіть режим спостереження: програма лише стежить за кнопкою й нічого не додає в кошик.'],
            ],
            'SEO – FAQ: test-povedinky' => [
                ['question' => 'Що робить прогрів профілів?', 'description' => 'Відтворює в відкритих профілях рухи курсора, паузи та прокрутку на сторінках товарів.'],
                ['question' => 'Чи додає прогрів товар у кошик?', 'description' => 'Ні. Прогрів не натискає кнопки купівлі й не додає товари в кошик.'],
            ],
        ];

        foreach (Module::where('name', 'like', 'SEO – FAQ:%')->get() as $module) {
            $setting = $module->setting->getArrayCopy();
            $items = $overrides[$module->name] ?? $setting['items'] ?? [];
            $setting['items'] = array_values(array_filter(
                $items,
                fn (array $item): bool => ! preg_match('/CAPTCHA|капч|гарант/iu', ($item['question'] ?? '').' '.($item['description'] ?? '')),
            ));
            $module->update(['setting' => $setting]);
        }
    }

    private function rewriteWarmupArticle(): void
    {
        $page = Page::where('slug', 'test-povedinky')->first();

        if (! $page) {
            return;
        }

        $description = 'Що робить прогрів профілів: рухи курсора, паузи, прокрутка й випадкові переходи. Як запустити на 1–60 хвилин для одного чи кількох профілів.';
        $page->update([
            'slug' => 'progriv-profiliv',
            'title' => 'Прогрів профілів AdsPower у Numis: що це і як запустити',
            'meta_title' => 'Прогрів профілів AdsPower в Numis: як запустити',
            'excerpt' => $description,
            'meta_description' => $description,
            'content' => <<<'HTML'
<p>Прогрів профілів відтворює у відкритих вкладках НБУ рухи курсора, паузи, прокрутку й, за бажанням, випадкові переходи сторінками товарів. Його запускають перед продажем, щоб профілі були активними.</p>
<h2>Як запустити прогрів</h2>
<ol>
<li>У розділі «Завдання» розгорніть блок «Прогрів профілів».</li>
<li>Оберіть профілі: усі або окремі.</li>
<li>Вкажіть тривалість у хвилинах, від 1 до 60.</li>
<li>За потреби відкрийте «Опції та журнал» і ввімкніть випадкові переходи сайтом.</li>
<li>Натисніть «Прогріти». Щоб зупинити раніше, натисніть «Зупинити прогрів».</li>
</ol>
<h2>Що відбувається під час прогріву</h2>
<ul>
<li>Курсор рухається різними траєкторіями, наводиться на елементи, робить паузи й прокручує сторінку.</li>
<li>Випадкові переходи відкривають лише сторінки товарів, із паузами 20–45 секунд.</li>
<li>Профілі працюють одночасно, а журнал показує кількість рухів, прокруток, переходів і пауз.</li>
<li>Прогрів не додає товари в кошик і не натискає кнопки купівлі.</li>
<li>Закриті профілі не запускаються: відкрийте їх заздалегідь.</li>
</ul>
<h2>Коли прогрів зупиняється</h2>
<p>Коли ви плануєте покупку або наближається її підготовка, прогрів зупиняється сам, щоб профіль був вільний. Якщо магазин повернув 429, переходи припиняються.</p>
<h2>Читайте також</h2>
<p><a href="/nalashtuvannya-adspower">Підключення AdsPower</a> · <a href="/cherha-ta-429">Черга й помилка 429</a> · <a href="/services/watch-mode">Режим спостереження</a></p>
HTML,
        ]);

        Page::query()->each(function (Page $item): void {
            if ($item->content && str_contains($item->content, '/test-povedinky')) {
                $item->update(['content' => str_replace('/test-povedinky', '/progriv-profiliv', $item->content)]);
            }
        });
    }

    private function addScreenshots(): void
    {
        $this->insertAfter('nalashtuvannya-adspower', '~<h2>Підключення за 5 кроків</h2>\s*<ol>.*?</ol>~su',
            $this->figure('connection', 488, 246, 'Розділ «Підключення до AdsPower» у Numis', 'Адреса Local API та ключ AdsPower вводяться в «Налаштуваннях».')
            .$this->figure('profiles', 488, 730, 'Збережені профілі в налаштуваннях Numis', 'Профілі додаються за ID з AdsPower і зручною назвою.'));

        $this->insertAfter('yak-kupyty-monetu-nbu-pershym', '~<h2>Що робить Numis</h2>\s*<ul>.*?</ul>~su',
            $this->figure('timing', 488, 462, 'Налаштування часу виконання в Numis', 'Час виконання: коли відкрити профіль, як часто оновлювати сторінку й скільки шукати кнопку після старту.'));
        $this->insertAfter('yak-kupyty-monetu-nbu-pershym', '~<h2>Як підготуватись</h2>\s*<ol>.*?</ol>~su',
            $this->figure('composer', 400, 659, 'Форма нового завдання в Numis', 'Нове завдання: посилання на монету, час старту за Києвом і профілі.'));

        $this->insertAfter('ne-dodaje-v-koshyk', '~<h2>Швидка перевірка</h2>\s*<ol>.*?</ol>~su',
            $this->figure('board', 728, 646, 'Черга завдань і журнал у Numis', 'Журнал завдання показує, що відбувалось і на якому етапі.'));

        $this->insertAfter('bot-dlya-kupivli-monet-nbu', '~<h2>Що Numis робить сам</h2>\s*<ul>.*?</ul>~su',
            $this->figure('tasks', 1440, 900, 'Головне вікно Numis із запланованими завданнями', 'Завдання в Numis: монета, час старту й профілі.', 'numis-tasks.png'));

        $this->insertAfter('progriv-profiliv', '~<h2>Як запустити прогрів</h2>\s*<ol>.*?</ol>~su',
            $this->figure('warmup', 400, 253, 'Блок «Прогрів профілів» у Numis', 'Блок «Прогрів профілів»: профілі, тривалість і опції.'));
    }

    private function insertAfter(string $slug, string $pattern, string $html): void
    {
        $page = Page::where('slug', $slug)->first();

        if (! $page || str_contains($page->content, $html)) {
            return;
        }

        $page->update(['content' => preg_replace($pattern, '$0'."\n".addcslashes($html, '\\$'), $page->content, 1)]);
    }

    private function figure(string $name, int $width, int $height, string $alt, string $caption, ?string $file = null): string
    {
        $file ??= 'numis-'.$name.'.png';

        return '<figure><img src="/images/app/'.$file.'" alt="'.e($alt).'" width="'.$width.'" height="'.$height.'" loading="lazy"><figcaption>'.e($caption).'</figcaption></figure>';
    }
};
