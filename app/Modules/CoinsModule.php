<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;
use App\Models\Coin;

class CoinsModule implements ModuleInterface
{
    /** Найближчі випуски: без них блок не показується, щоб не лишати порожню секцію. */
    public function draw(array $settings): string
    {
        $limit = max(3, min(24, (int) ($settings['limit'] ?? 6)));
        $coins = Coin::published()->upcoming()->limit($limit)->get();

        if ($coins->isEmpty()) {
            return '';
        }

        return view('modules.coins', ['settings' => $settings, 'coins' => $coins])->render();
    }
}
