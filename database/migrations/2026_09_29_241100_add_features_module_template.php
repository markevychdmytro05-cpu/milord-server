<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'features',
        ], [
            'name' => 'Features (можливості)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                    'items' => [
                        'label' => 'Картки',
                        'input' => 'repeatable',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Назва', 'type' => 'text'],
                            ['name' => 'text', 'label' => 'Текст', 'type' => 'textarea'],
                            ['name' => 'points', 'label' => 'Пункти (кожен з нового рядка)', 'type' => 'textarea'],
                            ['name' => 'icon', 'label' => 'Іконка', 'type' => 'select', 'options' => ['clock' => 'Годинник', 'monitor' => 'Монітор', 'eye' => 'Око', 'lock' => 'Замок', 'list' => 'Список', 'shield' => 'Щит']],
                        ],
                    ],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                    'items' => 'required|array|min:1',
                    'items.*.title' => 'required|string|max:120',
                    'items.*.text' => 'nullable|string|max:600',
                    'items.*.points' => 'nullable|string|max:600',
                    'items.*.icon' => 'nullable|string|max:20',
                ],
                'lang_fields' => [
                    'eyebrow' => ['label' => 'Надпис', 'input' => 'text'],
                    'title_1' => ['label' => 'Заголовок, рядок 1', 'input' => 'text'],
                    'title_2' => ['label' => 'Заголовок, рядок 2 (курсив)', 'input' => 'text'],
                    'description' => ['label' => 'Опис', 'input' => 'textarea'],
                ],
                'lang_rules' => [
                    'eyebrow' => 'nullable|string|max:120',
                    'title_1' => 'required|string|max:120',
                    'title_2' => 'nullable|string|max:120',
                    'description' => 'nullable|string|max:600',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'features')->delete();
    }
};
