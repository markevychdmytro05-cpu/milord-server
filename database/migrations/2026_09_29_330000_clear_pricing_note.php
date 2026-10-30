<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Module::where('name', 'Головна – Тарифи')->get() as $module) {
            $setting = $module->setting->toArray();
            $setting['uk']['note'] = '';
            $module->update(['setting' => $setting]);
        }
    }

    public function down(): void
    {
    }
};
