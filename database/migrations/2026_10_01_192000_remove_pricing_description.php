<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (ModuleTemplate::byCode('pricing')?->modules ?? [] as $module) {
            $setting = $module->setting->toArray();

            foreach (array_keys($setting) as $key) {
                if (is_array($setting[$key]) && array_key_exists('description', $setting[$key])) {
                    $setting[$key]['description'] = '';
                }
            }

            $module->update(['setting' => $setting]);
        }
    }

    public function down(): void
    {
    }
};
