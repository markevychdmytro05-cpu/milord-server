<?php

use App\Models\Module;
use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateModule('features', 'Головна – Можливості', function (array $setting): array {
            $setting['uk'] = ['title_1' => 'Що вміє Numis', 'title_2' => '', 'description' => ''];
            $setting['items'] = [
                ['icon' => 'clock', 'title' => 'Старт у точний час', 'text' => 'Програма працює за київським часом, що б не стояло на комп’ютері, і готує сторінку монети заздалегідь.', 'points' => ''],
                ['icon' => 'monitor', 'title' => 'Кілька профілів AdsPower', 'text' => 'Оберіть профілі зі списку, перевірте їх і запустіть одну монету одразу з усіх.', 'points' => ''],
                ['icon' => 'eye', 'title' => 'Режим спостереження', 'text' => 'Не хочете купувати? Програма просто повідомить, що кнопка з’явилась.', 'points' => ''],
                ['icon' => 'lock', 'title' => 'Ключ на ваш комп’ютер', 'text' => 'Ліцензія прив’язується до пристрою й до 72 годин працює без інтернету.', 'points' => ''],
                ['icon' => 'list', 'title' => 'Журнал спроб', 'text' => 'Кожен запуск записується: що сталося, коли і чим закінчилось.', 'points' => ''],
                ['icon' => 'shield', 'title' => 'Ваші дані лишаються у вас', 'text' => 'Профілі та ключ зберігаються на вашому комп’ютері. Сервер лише перевіряє ліцензію, паролів у нього немає.', 'points' => ''],
            ];

            return $setting;
        });

        $this->updateModule('steps', 'Головна – Як це працює', function (array $setting): array {
            $setting['uk'] = ['title_1' => 'Як почати', 'title_2' => '', 'description' => ''];

            return $setting;
        });

        $this->updateModule('pricing', 'Головна – Тарифи', function (array $setting): array {
            $setting['uk']['title_1'] = 'Оберіть пакет';
            $setting['uk']['title_2'] = '';

            return $setting;
        });

        $this->updateModule('cta-banner', 'Головна – Заклик', function (array $setting): array {
            $setting['uk']['title_1'] = 'Спробуйте Numis безкоштовно';
            $setting['uk']['title_2'] = '';
            $setting['uk']['button_text'] = 'Отримати пробний ключ';

            return $setting;
        });

        $this->updateModule('contact-form', 'Підвал – Форма зв’язку', function (array $setting): array {
            $setting['uk']['title_1'] = 'Напишіть нам';
            $setting['uk']['title_2'] = '';
            $setting['uk']['description'] = 'Розкажіть, як вам зручно зв’язатися, і ми відповімо.';

            return $setting;
        });

        $this->removeJargon();

        $dates = [31 => '2026-09-29', 30 => '2026-09-27', 29 => '2026-09-24', 28 => '2026-09-21', 27 => '2026-09-18', 25 => '2026-09-15', 24 => '2026-09-12'];
        foreach ($dates as $id => $date) {
            DB::table('pages')->where('id', $id)->whereNotNull('category')->update(['published_at' => $date.' 12:00:00']);
        }
    }

    private function updateModule(string $code, string $name, callable $change): void
    {
        $module = ModuleTemplate::byCode($code)?->modules()->where('name', $name)->first();

        $module?->update(['setting' => $change($module->setting->toArray())]);
    }

    private function removeJargon(): void
    {
        $replace = fn (string $text): string => str_replace(
            [' (Ed25519)', 'Відповіді сервера підписані'],
            ['', 'Відповіді сервера захищені цифровим підписом'],
            $text,
        );

        foreach (DB::table('pages')->where('content', 'like', '%Ed25519%')->get() as $page) {
            DB::table('pages')->where('id', $page->id)->update(['content' => $replace($page->content)]);
        }

        foreach (Module::where('setting', 'like', '%Ed25519%')->get() as $module) {
            $setting = json_decode($replace(json_encode($module->setting->toArray(), JSON_UNESCAPED_UNICODE)), true);
            $module->update(['setting' => $setting]);
        }
    }

    public function down(): void
    {
    }
};
