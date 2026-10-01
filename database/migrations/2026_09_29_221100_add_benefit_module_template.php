<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'benefit',
        ], [
            'name' => 'Benefit',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'image' => ['label' => 'Image', 'input' => 'image'],
                    'type' => ['label' => 'Type', 'input' => 'select', 'provider' => ['gray' => 'Gray', 'blue' => 'Blue']],
                    'align' => ['label' => 'Align', 'input' => 'select', 'provider' => ['left' => 'Left', 'right' => 'Right']],
                    'add_padding' => ['label' => 'Add top padding', 'input' => 'checkbox'],
                    'show_button' => ['label' => 'Show button', 'input' => 'checkbox'],
                    'button_url' => ['label' => 'Button URL', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'image' => 'nullable|image|max:4096',
                    'type' => 'required|string|in:gray,blue',
                    'align' => 'required|string|in:left,right',
                    'add_padding' => 'integer|between:0,1',
                    'show_button' => 'integer|between:0,1',
                    'button_url' => ['nullable', 'string', 'max:255', 'regex:~^(/|#|https?://|mailto:|tel:)~i'],
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Title', 'input' => 'text'],
                    'description' => ['label' => 'Description', 'input' => 'ckeditor'],
                    'button_text' => ['label' => 'Button text', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'nullable|string|max:255',
                    'description' => 'required|string',
                    'button_text' => 'nullable|string|max:100',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'benefit')->delete();
    }
};
