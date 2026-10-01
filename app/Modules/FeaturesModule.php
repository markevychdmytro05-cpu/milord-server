<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class FeaturesModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.features', compact('settings'))->render();
    }
}
