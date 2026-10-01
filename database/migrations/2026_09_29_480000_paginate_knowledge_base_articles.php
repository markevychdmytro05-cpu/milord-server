<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

/**
 * База знань розбита на сторінки: 9 статей (сітка 3×3) на сторінку.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Module::where('name', 'База знань – Статті')->get() as $module) {
            $module->update(['setting' => [...$module->setting->toArray(), 'limit' => 9]]);
        }
    }

    public function down(): void
    {
        foreach (Module::where('name', 'База знань – Статті')->get() as $module) {
            $module->update(['setting' => [...$module->setting->toArray(), 'limit' => 24]]);
        }
    }
};
