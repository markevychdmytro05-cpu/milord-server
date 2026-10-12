<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

/**
 * Картка «Режим спостереження» на головній: пояснює, що програма сама швидко оновлює сторінку замість користувача.
 */
return new class extends Migration
{
    public function up(): void
    {
        $module = Module::where('name', 'Головна – Можливості')->first();

        if (! $module) {
            return;
        }

        $setting = $module->setting->getArrayCopy();
        $setting['items'] = array_map(function (array $item): array {
            if (($item['title'] ?? '') === 'Режим спостереження') {
                $item['text'] = 'Не хочете, щоб програма купувала? Вона оновлюватиме сторінку замість вас за лічені мілісекунди.';
            }

            return $item;
        }, $setting['items'] ?? []);

        $module->update(['setting' => $setting]);
    }

    public function down(): void
    {
        //
    }
};
