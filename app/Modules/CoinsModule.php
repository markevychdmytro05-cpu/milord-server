<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;
use App\Models\Coin;
use App\Support\SiteLocale;

class CoinsModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        $limit = max(3, min(24, (int) ($settings['limit'] ?? 6)));
        $base = Coin::published()->withImage()->where('show_on_home', true);
        $coins = (clone $base)->upcoming()->limit($limit)->get();

        if ($coins->isEmpty()) {
            $coins = $base->chronological('desc')->limit($limit)->get();
            $locale = SiteLocale::current();
            $settings[$locale]['title'] = __('coins.module.recent_title');
            $settings[$locale]['description'] = __('coins.module.recent_description');
        }

        if ($coins->isEmpty()) {
            return '';
        }

        return view('modules.coins', ['settings' => $settings, 'coins' => $coins])->render();
    }
}
