<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;
use App\Models\Package;
use App\Support\OrderLink;

class PricingModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.pricing', [
            'settings' => $settings,
            'packages' => Package::forHome()->get(),
            'contactUrl' => OrderLink::url(),
        ])->render();
    }
}
