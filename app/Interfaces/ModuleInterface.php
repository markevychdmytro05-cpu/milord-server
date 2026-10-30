<?php

namespace App\Interfaces;

interface ModuleInterface
{
    public function draw(array $settings): string;
}
