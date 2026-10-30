<?php

use App\Models\MenuItem;
use App\Models\Module;
use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->where('header_button_text', 'Спробувати безкоштовно')->update(['header_button_text' => 'Пробний ключ на 3 дні']);

        $this->addHeroNoteField();
        $this->replaceTexts('hero', [
            'button_text' => ['Отримати ключ', 'Отримати пробний ключ'],
            'note' => [null, '3 дні безкоштовно, без оплати. Ключ надсилаємо після заявки.'],
        ]);
        $this->replaceTexts('pricing', [
            'title_1' => ['Оберіть пакет', 'Тарифи'],
            'order_text' => ['Замовити', 'Замовити тариф'],
        ]);
        $this->replaceTexts('contact-form', [
            'title_1' => ['Отримайте пробний ключ', 'Отримайте пробний ключ на 3 дні'],
            'button_text' => ['Надіслати заявку', 'Отримати пробний ключ'],
            'success_text' => ['Дякуємо! Ми зв’яжемося з вами найближчим часом.', 'Дякуємо! Напишемо вам із ключем найближчим часом.'],
        ]);
        $this->replaceTexts('stats', ['title' => ['Цифри, які ми виміряли', 'Numis у цифрах']]);

        $this->mergeFeaturesMenu();
    }

    public function down(): void
    {
    }

    private function addHeroNoteField(): void
    {
        $template = ModuleTemplate::byCode('hero');

        if (! $template) {
            return;
        }

        $config = $template->template->toArray();
        $config['lang_fields'] += ['note' => ['label' => 'Примітка під кнопками', 'input' => 'text']];
        $config['lang_rules'] += ['note' => 'nullable|string|max:160'];
        $template->update(['template' => $config]);
    }

    private function replaceTexts(string $templateCode, array $changes): void
    {
        $template = ModuleTemplate::byCode($templateCode);

        foreach ($template ? Module::where('module_template_id', $template->id)->get() : [] as $module) {
            $setting = $module->setting->toArray();
            $before = $setting;

            foreach ($changes as $field => [$old, $new]) {
                $current = $setting['uk'][$field] ?? null;

                if ($current === $old || ($old === null && blank($current))) {
                    $setting['uk'][$field] = $new;
                }
            }

            if ($setting !== $before) {
                $module->update(['setting' => $setting]);
            }
        }
    }

    private function mergeFeaturesMenu(): void
    {
        $features = MenuItem::where('menu', MenuItem::HEADER)->whereNull('parent_id')->where('url', '/#features')->first();
        $services = MenuItem::where('menu', MenuItem::HEADER)->whereNull('parent_id')->where('title', 'Послуги')->first();

        if (! $features || ! $services || ! MenuItem::where('parent_id', $services->id)->exists()) {
            return;
        }

        MenuItem::where('parent_id', $services->id)->update(['parent_id' => $features->id]);
        $services->update(['is_active' => false]);

        $items = MenuItem::where('menu', MenuItem::HEADER)->orderBy('lft')->orderBy('id')->get()->groupBy('parent_id');
        $counter = 0;
        $walk = function (?int $parentId, int $depth) use (&$walk, &$counter, $items): void {
            foreach ($items[$parentId] ?? [] as $item) {
                $lft = ++$counter;
                $walk($item->id, $depth + 1);
                $item->update(['lft' => $lft, 'rgt' => ++$counter, 'depth' => $depth]);
            }
        };
        $walk(null, 1);
    }
};
