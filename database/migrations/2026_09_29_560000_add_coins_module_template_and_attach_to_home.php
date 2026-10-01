<?php

use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const MODULE_NAME = 'Головна – Найближчі випуски';

    public function up(): void
    {
        $template = ModuleTemplate::firstOrCreate([
            'code' => 'coins',
        ], [
            'name' => 'Coins (найближчі випуски монет)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                    'limit' => ['label' => 'Скільки монет показувати', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                    'limit' => 'nullable|integer|between:3,24',
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Заголовок', 'input' => 'text'],
                    'description' => ['label' => 'Опис', 'input' => 'textarea'],
                    'button_text' => ['label' => 'Текст кнопки «Усі монети»', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'nullable|string|max:120',
                    'description' => 'nullable|string|max:600',
                    'button_text' => 'nullable|string|max:60',
                ],
            ],
        ]);

        $home = Page::where('slug', Page::ROOT_SLUG)->first();

        if (! $home || Module::where('name', self::MODULE_NAME)->exists()) {
            return;
        }

        $module = $template->makeModule(self::MODULE_NAME, [
            'active' => 1,
            'anchor' => 'coins',
            'limit' => 6,
            'uk' => [
                'title' => 'Найближчі випуски монет НБУ',
                'description' => 'Які монети вийдуть найближчим часом за планом Нацбанку.',
                'button_text' => 'Усі монети',
            ],
        ]);

        $ids = $home->page_modules;
        $faqId = Module::where('name', 'Головна – FAQ')->value('id');
        $position = $faqId ? array_search($faqId, $ids, true) : false;

        array_splice($ids, $position === false ? count($ids) : $position, 0, [$module->id]);
        $home->update(['page_modules' => $ids]);
    }

    public function down(): void
    {
        Module::where('name', self::MODULE_NAME)->delete();
        ModuleTemplate::where('code', 'coins')->delete();
    }
};
