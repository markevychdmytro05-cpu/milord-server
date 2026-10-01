<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class ContactFormModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.contact-form', compact('settings'))->render();
    }
}
