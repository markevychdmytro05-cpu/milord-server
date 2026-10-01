<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * З hero прибрано лічильник акаунтів і двохрядкову примітку під кнопками.
 */
return new class extends Migration
{
    public function up(): void
    {
        $template = ModuleTemplate::byCode('hero');

        if (! $template) {
            return;
        }

        $config = $template->template->toArray();
        unset($config['fields']['show_stat'], $config['rules']['show_stat']);

        foreach (['stat_label', 'tag', 'tag_note'] as $field) {
            unset($config['lang_fields'][$field], $config['lang_rules'][$field]);
        }

        $template->update(['template' => $config]);

        foreach ($template->modules as $module) {
            $setting = $module->setting->toArray();
            unset($setting['show_stat']);

            foreach (array_keys($setting) as $key) {
                if (is_array($setting[$key])) {
                    unset($setting[$key]['stat_label'], $setting[$key]['tag'], $setting[$key]['tag_note']);
                }
            }

            $module->update(['setting' => $setting]);
        }
    }

    public function down(): void
    {
        //
    }
};
