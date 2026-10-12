<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class StatsModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.stats', compact('settings'))->render();
    }
}
