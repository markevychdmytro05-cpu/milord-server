<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'pricing',
        ], [
            'name' => 'Pricing (тарифи)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                ],
                'lang_fields' => [
                    'eyebrow' => ['label' => 'Надпис', 'input' => 'text'],
                    'title_1' => ['label' => 'Заголовок, рядок 1', 'input' => 'text'],
                    'title_2' => ['label' => 'Заголовок, рядок 2 (курсив)', 'input' => 'text'],
                    'description' => ['label' => 'Опис', 'input' => 'textarea'],
                    'note' => ['label' => 'Примітка під тарифами', 'input' => 'text'],
                    'empty_text' => ['label' => 'Текст, якщо тарифів немає', 'input' => 'text'],
                    'order_text' => ['label' => 'Текст кнопки замовлення', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'eyebrow' => 'nullable|string|max:120',
                    'title_1' => 'required|string|max:120',
                    'title_2' => 'nullable|string|max:120',
                    'description' => 'nullable|string|max:600',
                    'note' => 'nullable|string|max:300',
                    'empty_text' => 'nullable|string|max:200',
                    'order_text' => 'nullable|string|max:80',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'pricing')->delete();
    }
};
