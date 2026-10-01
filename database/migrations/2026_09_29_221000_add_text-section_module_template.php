<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'text-section',
        ], [
            'name' => 'Text section',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'type' => ['label' => 'Type', 'input' => 'select', 'provider' => ['light' => 'Light', 'gray' => 'Gray']],
                    'position' => ['label' => 'Position', 'input' => 'select', 'provider' => ['left' => 'Left', 'right' => 'Right']],
                    'upload' => ['label' => 'Image', 'input' => 'upload'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'type' => 'required|string|in:light,gray',
                    'position' => 'required|string|in:left,right',
                    'upload' => 'nullable|image|max:4096',
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Title', 'input' => 'text'],
                    'description' => ['label' => 'Description', 'input' => 'ckeditor'],
                    'url' => ['label' => 'Button URL', 'input' => 'text'],
                    'text_url' => ['label' => 'Button text', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'url' => ['nullable', 'string', 'max:255', 'regex:~^(/|#|https?://|mailto:|tel:)~i'],
                    'text_url' => 'nullable|string|max:100',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'text-section')->delete();
    }
};
