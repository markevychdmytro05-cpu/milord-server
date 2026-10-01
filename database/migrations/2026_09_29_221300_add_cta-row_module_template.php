<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ModuleTemplate::firstOrCreate([
            'code' => 'cta-row',
        ], [
            'name' => 'CTA row',
            'template' => [
                'fields' => [
                    'active' => ['label' => 'Видимість', 'input' => 'checkbox'],
                    'bg_gray' => ['label' => 'Gray background', 'input' => 'checkbox'],
                    'url' => ['label' => 'Button URL', 'input' => 'text'],
                ],
                'rules' => [
                    'active' => 'integer|between:0,1',
                    'bg_gray' => 'integer|between:0,1',
                    'url' => ['required', 'string', 'max:255', 'regex:~^(/|#|https?://|mailto:|tel:)~i'],
                ],
                'lang_fields' => [
                    'title' => ['label' => 'Title', 'input' => 'text'],
                    'text' => ['label' => 'Text', 'input' => 'textarea'],
                    'button_text' => ['label' => 'Button text', 'input' => 'text'],
                ],
                'lang_rules' => [
                    'title' => 'required|string|max:255',
                    'text' => 'nullable|string|max:1000',
                    'button_text' => 'required|string|max:100',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        ModuleTemplate::where('code', 'cta-row')->delete();
    }
};
