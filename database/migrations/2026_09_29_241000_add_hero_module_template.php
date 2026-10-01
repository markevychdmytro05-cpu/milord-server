<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'hero',
        ], [
            'name' => 'Hero (головний екран)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'show_stat' => ['label' => 'Показувати «до N акаунтів» (з тарифів)', 'input' => 'checkbox'],
                    'button_url' => ['label' => 'Адреса кнопки', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'show_stat' => 'integer|between:0,1',
                    'button_url' => ['nullable', 'string', 'max:255', 'regex:~^(/|#|https?://|mailto:|tel:)~i'],
                ],
                'lang_fields' => [
                    'badge' => ['label' => 'Надпис над заголовком', 'input' => 'text'],
                    'title_1' => ['label' => 'Заголовок, рядок 1', 'input' => 'text'],
                    'title_2' => ['label' => 'Заголовок, рядок 2 (курсив)', 'input' => 'text'],
                    'lead' => ['label' => 'Підзаголовок', 'input' => 'textarea'],
                    'tag' => ['label' => 'Примітка (жирна)', 'input' => 'text'],
                    'tag_note' => ['label' => 'Примітка (сіра)', 'input' => 'text'],
                    'stat_label' => ['label' => 'Підпис лічильника', 'input' => 'text'],
                    'button_text' => ['label' => 'Текст кнопки', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'badge' => 'nullable|string|max:120',
                    'title_1' => 'required|string|max:120',
                    'title_2' => 'nullable|string|max:120',
                    'lead' => 'nullable|string|max:600',
                    'tag' => 'nullable|string|max:200',
                    'tag_note' => 'nullable|string|max:200',
                    'stat_label' => 'nullable|string|max:60',
                    'button_text' => 'nullable|string|max:80',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'hero')->delete();
    }
};
