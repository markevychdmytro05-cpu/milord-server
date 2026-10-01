<?php

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Головні сторінки з підвалу з'являються і в меню шапки: «Послуги» (випадаючий список), «Про нас», «Контакти».
 */
return new class extends Migration
{
    public function up(): void
    {
        if (MenuItem::where('menu', MenuItem::HEADER)->where('title', 'Послуги')->exists()) {
            return;
        }

        $services = Page::where('type', Page::TYPE_SERVICE)->whereIn('slug', ['auto-buy', 'adspower-profiles', 'watch-mode', 'device-license', 'task-journal'])
            ->orderByRaw("CASE slug WHEN 'auto-buy' THEN 1 WHEN 'adspower-profiles' THEN 2 WHEN 'watch-mode' THEN 3 WHEN 'device-license' THEN 4 ELSE 5 END")->get();
        $about = Page::where('type', Page::TYPE_PAGE)->where('slug', 'about')->first();

        $position = (int) MenuItem::where('menu', MenuItem::HEADER)->max('rgt');
        $base = ['menu' => MenuItem::HEADER, 'is_active' => true];

        if ($services->isNotEmpty()) {
            $root = MenuItem::create($base + ['title' => 'Послуги', 'depth' => 1, 'lft' => ++$position, 'rgt' => $position]);

            foreach ($services as $service) {
                MenuItem::create($base + ['parent_id' => $root->id, 'page_id' => $service->id, 'depth' => 2, 'lft' => ++$position, 'rgt' => ++$position]);
            }

            $root->update(['rgt' => ++$position]);
        }

        if ($about) {
            MenuItem::create($base + ['page_id' => $about->id, 'depth' => 1, 'lft' => ++$position, 'rgt' => ++$position]);
        }

        MenuItem::create($base + ['title' => 'Контакти', 'url' => '/#contact', 'depth' => 1, 'lft' => ++$position, 'rgt' => ++$position]);
    }

    public function down(): void
    {
        MenuItem::where('menu', MenuItem::HEADER)->whereIn('title', ['Послуги', 'Контакти'])->delete();
    }
};
