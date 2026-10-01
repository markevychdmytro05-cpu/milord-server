<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'contact-form',
        ], [
            'name' => 'Contact form (форма зв’язку)',
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
                    'name_label' => ['label' => 'Підпис поля «Імʼя»', 'input' => 'text'],
                    'contact_label' => ['label' => 'Підпис поля «Контакт»', 'input' => 'text'],
                    'message_label' => ['label' => 'Підпис поля «Повідомлення»', 'input' => 'text'],
                    'button_text' => ['label' => 'Текст кнопки', 'input' => 'text'],
                    'success_text' => ['label' => 'Текст після відправки', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'eyebrow' => 'nullable|string|max:120',
                    'title_1' => 'required|string|max:120',
                    'title_2' => 'nullable|string|max:120',
                    'description' => 'nullable|string|max:600',
                    'name_label' => 'required|string|max:60',
                    'contact_label' => 'required|string|max:60',
                    'message_label' => 'nullable|string|max:60',
                    'button_text' => 'required|string|max:80',
                    'success_text' => 'required|string|max:200',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'contact-form')->delete();
    }
};
