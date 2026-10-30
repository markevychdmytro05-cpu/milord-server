<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::where('name', 'Головна – Цифри')->first();

        if (! $module) {
            return;
        }

        $setting = $module->setting->getArrayCopy();
        $setting['items'] = array_map(function (array $item): array {
            unset($item['note']);

            return $item;
        }, $setting['items'] ?? []);

        $module->update(['setting' => $setting]);
    }

    public function down(): void
    {
    }
};
