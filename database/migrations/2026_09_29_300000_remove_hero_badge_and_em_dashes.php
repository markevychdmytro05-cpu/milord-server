<?php

use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Прибирає надпис-значок із hero та замінює довгі тире (—) на середні (–) у збережених даних сайту.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->removeHeroBadge();
        $this->replaceDashes();
    }

    public function down(): void
    {
        //
    }

    private function removeHeroBadge(): void
    {
        $template = ModuleTemplate::byCode('hero');

        if (! $template) {
            return;
        }

        $config = $template->template->toArray();
        unset($config['lang_fields']['badge'], $config['lang_rules']['badge']);
        $template->update(['template' => $config]);

        foreach ($template->modules as $module) {
            $setting = $module->setting->toArray();

            foreach (array_keys($setting) as $key) {
                if (is_array($setting[$key])) {
                    unset($setting[$key]['badge']);
                }
            }

            $module->update(['setting' => $setting]);
        }
    }

    private function replaceDashes(): void
    {
        $swap = fn ($value) => is_string($value) ? str_replace('—', '–', $value) : $value;

        foreach (Module::all() as $module) {
            $setting = $module->setting->toArray();
            array_walk_recursive($setting, function (&$value) use ($swap): void {
                $value = $swap($value);
            });

            $module->update(['name' => $swap($module->name), 'setting' => $setting]);
        }

        foreach (Page::all() as $page) {
            $page->update(collect(['title', 'excerpt', 'content', 'meta_title', 'meta_description'])
                ->mapWithKeys(fn (string $field): array => [$field => $swap($page->{$field})])->all());
        }

        $site = SiteSetting::current();
        $site->update(collect(['header_logo', 'header_button_text', 'footer_text', 'footer_note', 'contact_address'])
            ->mapWithKeys(fn (string $field): array => [$field => $swap($site->{$field})])->all());

        foreach (DB::table('menu_items')->whereNotNull('title')->get(['id', 'title']) as $item) {
            DB::table('menu_items')->where('id', $item->id)->update(['title' => $swap($item->title)]);
        }
    }
};
