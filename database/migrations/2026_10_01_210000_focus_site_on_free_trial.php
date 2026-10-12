<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Головна дія сайту: отримати пробний ключ (кнопка в шапці, hero і форма). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->update(['header_button_text' => 'Спробувати безкоштовно', 'header_button_url' => '/#contact']);

        $this->updateModule('hero', 'Головна – Hero', function (array $setting): array {
            $setting['uk']['button_text'] = 'Отримати ключ';
            $setting['button_url'] = '#contact';
            $setting['uk']['button2_text'] = 'Тарифи';
            $setting['button2_url'] = '#pricing';

            return $setting;
        });

        $this->updateModule('contact-form', 'Підвал – Форма зв’язку', function (array $setting): array {
            $setting['uk']['title_1'] = 'Отримайте пробний ключ';
            $setting['uk']['title_2'] = '';
            $setting['uk']['description'] = 'Залиште контакт, і ми видамо ключ на 3 дні безкоштовно. Тут же можна поставити будь-яке запитання.';
            $setting['uk']['button_text'] = 'Надіслати заявку';

            return $setting;
        });
    }

    /** @param  callable(array<string, mixed>): array<string, mixed>  $change */
    private function updateModule(string $code, string $name, callable $change): void
    {
        $module = ModuleTemplate::byCode($code)?->modules()->where('name', $name)->first();

        $module?->update(['setting' => $change($module->setting->toArray())]);
    }

    public function down(): void
    {
        //
    }
};
