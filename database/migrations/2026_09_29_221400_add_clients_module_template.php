<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'clients',
        ], [
            'name' => 'Clients',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'items' => [
                        'label' => 'Clients',
                        'input' => 'repeatable',
                        'fields' => [
                            ['name' => 'image', 'label' => 'Logo (URL or /path)', 'type' => 'text'],
                            ['name' => 'url', 'label' => 'Link', 'type' => 'text'],
                        ],
                    ],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'items' => 'required|array|min:1',
                    'items.*.image' => 'required|string|max:2048',
                    'items.*.url' => 'required|string|max:255',
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
        ModuleTemplate::where('code', 'clients')->delete();
    }
};
