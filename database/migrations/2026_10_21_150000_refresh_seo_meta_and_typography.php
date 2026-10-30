<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const META = [
        '/' => [
            'old_description' => 'Numis стежить за кнопкою «Купити» на сторінці монети НБУ й додає її в кошик у момент появи. Профілі AdsPower, час за Києвом, ліцензія на пристрій. Тарифи та FAQ.',
            'meta_description' => 'Numis для Windows і macOS стежить за кнопкою «Купити» в магазині НБУ й додає монету в кошик у момент появи. 3 дні безкоштовно.',
        ],
        'avtomatychna-kupivlia-monet-nbu' => [
            'old_description' => 'Програма стежить за сторінкою монети й додає її в кошик у момент появи кнопки.',
            'meta_title' => 'Додавання монети НБУ в кошик у момент старту | Numis',
            'meta_description' => 'Numis готує профіль і сторінку заздалегідь і в момент появи кнопки додає монету НБУ в кошик. Старт за київським часом.',
        ],
        'profili-adspower' => [
            'old_description' => 'Кілька акаунтів одночасно через локальний API AdsPower.',
            'meta_title' => 'Профілі AdsPower для купівлі монет НБУ | Numis',
            'meta_description' => 'Запускайте купівлю монети НБУ одразу в кількох профілях AdsPower через Local API. Профілі перевіряються перед стартом.',
        ],
        'rezym-sposterezennia' => [
            'old_description' => 'Стежте за кнопкою купівлі без жодних дій у магазині.',
            'meta_title' => 'Відстеження кнопки «Купити» на сайті НБУ | Numis',
            'meta_description' => 'Режим спостереження повідомить, коли монета НБУ стане доступною, і нічого не купуватиме. Зручно для перевірки налаштувань.',
        ],
        'litsenziia-na-prystriy' => [
            'old_description' => 'Ключ прив’язується до комп’ютера, відповіді сервера підписані.',
            'meta_title' => 'Ліцензія Numis на пристрій: активація й офлайн-доступ',
            'meta_description' => 'Ключ Numis прив’язується до вашого комп’ютера, відповіді сервера підписані, офлайн програма працює до 72 годин.',
        ],
        'zurnal-zavdan' => [
            'old_description' => 'Статуси, час і результат кожної спроби зберігаються в програмі.',
            'meta_title' => 'Журнал завдань Numis: статуси й результати спроб',
            'meta_description' => 'Журнал зберігає статус, час і результат кожної спроби купівлі монети НБУ, історія доступна й після завершення завдання.',
        ],
    ];

    public function up(): void
    {
        foreach (self::META as $slug => $meta) {
            $page = DB::table('pages')->where('slug', $slug)->first();

            if (! $page || $page->meta_description !== $meta['old_description']) {
                continue;
            }

            DB::table('pages')->where('id', $page->id)->update(array_filter([
                'meta_title' => blank($page->meta_title) ? ($meta['meta_title'] ?? null) : null,
                'meta_description' => $meta['meta_description'],
            ]));
        }

        $swap = fn ($value) => is_string($value) ? str_replace(['—', 'ʼ'], ['–', '’'], $value) : $value;

        foreach (DB::table('pages')->get(['id', 'title', 'meta_title', 'meta_description', 'excerpt', 'content']) as $page) {
            $fields = ['title', 'meta_title', 'meta_description', 'excerpt', 'content'];
            $changed = array_filter(
                array_combine($fields, array_map(fn (string $field) => $swap($page->{$field}), $fields)),
                fn ($value, string $field) => $value !== $page->{$field},
                ARRAY_FILTER_USE_BOTH,
            );

            if ($changed !== []) {
                DB::table('pages')->where('id', $page->id)->update($changed);
            }
        }

        foreach (Module::all() as $module) {
            $setting = $module->setting->toArray();
            $before = $setting;
            array_walk_recursive($setting, function (&$value) use ($swap): void {
                $value = $swap($value);
            });

            if ($setting !== $before) {
                $module->update(['setting' => $setting]);
            }
        }
    }

    public function down(): void
    {
    }
};
