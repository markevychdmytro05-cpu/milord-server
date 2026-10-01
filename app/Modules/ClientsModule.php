<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;

class ClientsModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        return view('modules.clients', compact('settings'))->render();
    }
}
