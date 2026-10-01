<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class HeroModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.hero', compact('settings'))->render();
    }
}
