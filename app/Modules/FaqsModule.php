<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class FaqsModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        if (empty($settings['items'])) {
            return '';
        }

        return view('modules.faq', compact('settings'))->render();
    }
}
