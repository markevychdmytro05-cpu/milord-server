<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class TextSectionModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.text-section', compact('settings'))->render();
    }
}
