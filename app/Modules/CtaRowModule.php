<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class CtaRowModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.cta-row', compact('settings'))->render();
    }
}
