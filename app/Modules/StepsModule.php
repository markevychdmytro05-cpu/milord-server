<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class StepsModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.steps', compact('settings'))->render();
    }
}
