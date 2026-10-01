<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;
use App\Support\OrderLink;

class CtaBannerModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        $contactUrl = OrderLink::url();

        return view('modules.cta-banner', [
            'settings' => $settings,
            'url' => ($settings['button_url'] ?? '') ?: ($contactUrl ?: '#pricing'),
            'external' => ! ($settings['button_url'] ?? '') && $contactUrl,
        ])->render();
    }
}
