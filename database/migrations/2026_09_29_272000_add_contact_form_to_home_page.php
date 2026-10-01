<?php

use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Форма зв'язку додається на головну перед завершальним закликом.
 */
return new class extends Migration
{
    public function up(): void
    {
        $home = Page::where('slug', Page::ROOT_SLUG)->first();

        if (! $home || Module::where('name', 'Головна – Форма зв’язку')->exists()) {
            return;
        }

        $module = ModuleTemplate::byCode('contact-form')->makeModule('Головна – Форма зв’язку', [
            'active' => 1,
            'anchor' => 'contact',
            'uk' => [
                'eyebrow' => 'Звʼязок',
                'title_1' => 'Залиште',
                'title_2' => 'заявку',
                'description' => 'Напишіть, як з вами зручно зв’язатися, і ми відповімо найближчим часом.',
                'name_label' => 'Імʼя',
                'contact_label' => 'Телефон, Telegram або email',
                'message_label' => 'Повідомлення',
                'button_text' => 'Надіслати',
                'success_text' => 'Дякуємо! Ми звʼяжемося з вами найближчим часом.',
            ],
        ]);

        $ids = $home->page_modules;
        $last = array_pop($ids);
        $ids = [...$ids, $module->id];

        if ($last !== null) {
            $ids[] = $last;
        }

        $home->update(['page_modules' => $ids]);
    }

    public function down(): void
    {
        Module::where('name', 'Головна – Форма зв’язку')->delete();
    }
};
