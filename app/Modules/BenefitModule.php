<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class BenefitModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.benefit', compact('settings'))->render();
    }
}
