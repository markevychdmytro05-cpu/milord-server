<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'cta-banner',
        ], [
            'name' => 'CTA banner (чорний заклик)',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'anchor' => ['label' => 'Якір (id секції)', 'input' => 'text'],
                    'button_url' => ['label' => 'Адреса кнопки (порожньо – посилання замовлення або тарифи)', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
                    'button_url' => ['nullable', 'string', 'max:255', 'regex:~^(/|#|https?://|mailto:|tel:)~i'],
                ],
                'lang_fields' => [
                    'eyebrow' => ['label' => 'Надпис', 'input' => 'text'],
                    'title_1' => ['label' => 'Заголовок, рядок 1', 'input' => 'text'],
                    'title_2' => ['label' => 'Заголовок, рядок 2 (курсив)', 'input' => 'text'],
                    'button_text' => ['label' => 'Текст кнопки', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'eyebrow' => 'nullable|string|max:120',
                    'title_1' => 'required|string|max:120',
                    'title_2' => 'nullable|string|max:120',
                    'button_text' => 'required|string|max:80',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'cta-banner')->delete();
    }
};
