<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = ModuleTemplate::firstOrCreate(['code' => 'stats'], [
            'name' => 'Stats (цифри)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                    'items' => [
                        'label' => 'Цифри',
                        'input' => 'repeatable',
                        'fields' => [
                            ['name' => 'value', 'label' => 'Значення', 'type' => 'text'],
                            ['name' => 'unit', 'label' => 'Одиниця', 'type' => 'text'],
                            ['name' => 'label', 'label' => 'Що вимірюється', 'type' => 'text'],
                            ['name' => 'note', 'label' => 'Як і де виміряно', 'type' => 'textarea'],
                        ],
                    ],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                    'items' => 'required|array|min:1',
                    'items.*.value' => 'required|string|max:30',
                    'items.*.unit' => 'nullable|string|max:30',
                    'items.*.label' => 'required|string|max:160',
                    'items.*.note' => 'nullable|string|max:400',
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Заголовок', 'input' => 'text'],
                    'description' => ['label' => 'Опис', 'input' => 'textarea'],
                ],
                'lang_rules' => [
                    'title' => 'nullable|string|max:120',
                    'description' => 'nullable|string|max:400',
                ],
            ],
        ]);

        $home = DB::table('pages')->where('slug', '/')->value('id');

        if ($home && ! $template->modules()->where('name', 'Головна – Цифри')->exists()) {
            $module = $template->makeModule('Головна – Цифри', [
                'active' => 1,
                'anchor' => 'numbers',
                'items' => [
                    [
                        'value' => '35', 'unit' => 'мс',
                        'label' => 'від появи кнопки до кліку на реальному продажі',
                        'note' => 'Продаж НБУ 29.09.2026, два профілі. Завантаження сторінки й черга НБУ в цей час не входять.',
                    ],
                    [
                        'value' => '4,6', 'unit' => 'мс',
                        'label' => 'реакція програми на появу кнопки',
                        'note' => 'Локальний тест без мережі: медіана 4,6 мс, максимум 7,4 мс, 8 запусків.',
                    ],
                    [
                        'value' => '20', 'unit' => 'акаунтів',
                        'label' => 'одночасно в тарифі «Про»',
                        'note' => 'Кожен акаунт працює у власному профілі AdsPower.',
                    ],
                ],
                'uk' => [
                    'title' => 'Цифри, які ми виміряли',
                ],
            ]);

            DB::table('page_module')->where('page_id', $home)->where('sort_order', '>=', 1)->increment('sort_order');
            DB::table('page_module')->insert(['page_id' => $home, 'module_id' => $module->id, 'sort_order' => 1]);
        }

        $changelog = <<<'HTML'
<p>Журнал змін програми за датами.</p>

<h2>1 жовтня 2026</h2>
<ul>
<li><strong>Посилили захист.</strong> Ліцензія надійніше прив’язана до вашого пристрою.</li>
<li><strong>Виправили дрібні помилки</strong> в роботі з ключем.</li>
</ul>

<h2>29 вересня 2026</h2>
<ul>
<li><strong>Покращили швидкість.</strong> Програма швидше реагує на появу кнопки «Купити».</li>
<li><strong>Оптимізували роботу з профілями AdsPower</strong>, тож підготовка до старту займає менше часу.</li>
<li><strong>Додали активацію за ключем</strong> і перевірку ліцензії у фоні.</li>
</ul>

<h2>27 вересня 2026</h2>
<ul>
<li><strong>Точніший час старту.</strong> Покращили синхронізацію часу перед продажем.</li>
<li><strong>Виправили помилки</strong> у розрахунку часу покупки.</li>
</ul>

<h2>26 вересня 2026</h2>
<ul>
<li><strong>Перший випуск.</strong> Планувальник завдань, робота з профілями AdsPower, режими «Спостерігати без покупки» та «Додати монету в кошик».</li>
</ul>
HTML;

        $page = DB::table('pages')->where('type', 'page')->where('slug', 'shcho-novoho')->first();
        $attributes = [
            'title' => 'Що нового в Numis',
            'excerpt' => 'Журнал змін програми за датами: ліцензування, швидкість реакції, точність часу старту.',
            'content' => $changelog,
            'meta_title' => 'Що нового в Numis: журнал змін програми',
            'meta_description' => 'Журнал змін Numis за датами: активація за ключем, прив’язка до пристрою, швидкість реакції на кнопку «Купити», точність часу старту.',
            'is_published' => true,
            'show_title' => true,
            'updated_at' => now(),
        ];

        if ($page) {
            DB::table('pages')->where('id', $page->id)->update($attributes);
            $pageId = $page->id;
        } else {
            $pageId = DB::table('pages')->insertGetId($attributes + ['type' => 'page', 'slug' => 'shcho-novoho', 'created_at' => now()]);
        }

        $cta = ModuleTemplate::byCode('cta-row')?->modules()->where('name', 'SEO – Заклик: пробний період')->first();

        if ($cta && ! DB::table('page_module')->where('page_id', $pageId)->where('module_id', $cta->id)->exists()) {
            DB::table('page_module')->insert(['page_id' => $pageId, 'module_id' => $cta->id, 'sort_order' => 0]);
        }

        if (! DB::table('menu_items')->where('menu', 'footer')->where('page_id', $pageId)->exists()) {
            $next = (int) DB::table('menu_items')->where('menu', 'footer')->max('rgt') + 1;

            DB::table('menu_items')->insert([
                'menu' => 'footer', 'parent_id' => null, 'lft' => $next, 'rgt' => $next + 1, 'depth' => 1,
                'title' => 'Що нового', 'url' => null, 'page_id' => $pageId, 'open_in_new_tab' => false, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $pageId = DB::table('pages')->where('type', 'page')->where('slug', 'shcho-novoho')->value('id');

        if ($pageId) {
            DB::table('menu_items')->where('page_id', $pageId)->delete();
            DB::table('pages')->where('id', $pageId)->delete();
        }

        $template = ModuleTemplate::byCode('stats');
        $template?->modules()->each(fn ($module) => $module->delete());
        $template?->delete();
    }
};
