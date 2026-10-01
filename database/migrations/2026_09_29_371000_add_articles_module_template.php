<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'articles',
        ], [
            'name' => 'Articles (сітка статей)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                    'show_filters' => ['label' => 'Показувати фільтр за категоріями', 'input' => 'checkbox'],
                    'limit' => ['label' => 'Скільки статей показувати', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                    'show_filters' => 'integer|between:0,1',
                    'limit' => 'nullable|integer|between:3,60',
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Заголовок', 'input' => 'text'],
                    'description' => ['label' => 'Опис', 'input' => 'textarea'],
                    'all_label' => ['label' => 'Підпис фільтра «Усі»', 'input' => 'text'],
                    'empty_text' => ['label' => 'Текст, якщо статей немає', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'nullable|string|max:120',
                    'description' => 'nullable|string|max:600',
                    'all_label' => 'nullable|string|max:40',
                    'empty_text' => 'nullable|string|max:200',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'articles')->delete();
    }
};
