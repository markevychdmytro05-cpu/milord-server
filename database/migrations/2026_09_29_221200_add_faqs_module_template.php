<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'faqs',
        ], [
            'name' => 'FAQ',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'items' => [
                        'label' => 'Questions',
                        'input' => 'repeatable',
                        'fields' => [
                            ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
                            ['name' => 'description', 'label' => 'Answer', 'type' => 'textarea'],
                        ],
                    ],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'items' => 'nullable|array',
                    'items.*.question' => 'required|string|max:255',
                    'items.*.description' => 'required|string',
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Title', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'nullable|string|max:255',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'faqs')->delete();
    }
};
