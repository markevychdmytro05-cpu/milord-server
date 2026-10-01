<?php

use App\Models\ModuleTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Прибирає надписи-«пілюлі» над заголовками секцій і напис «Europe/Kyiv» у футері.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['features', 'steps', 'pricing', 'cta-banner', 'contact-form'] as $code) {
            $template = ModuleTemplate::byCode($code);

            if (! $template) {
                continue;
            }

            $config = $template->template->toArray();
            unset($config['lang_fields']['eyebrow'], $config['lang_rules']['eyebrow']);
            $template->update(['template' => $config]);

            foreach ($template->modules as $module) {
                $setting = $module->setting->toArray();

                foreach (array_keys($setting) as $key) {
                    if (is_array($setting[$key])) {
                        unset($setting[$key]['eyebrow']);
                    }
                }

                $module->update(['setting' => $setting]);
            }
        }

        DB::table('site_settings')->update(['footer_note' => null]);
    }

    public function down(): void
    {
        //
    }
};
